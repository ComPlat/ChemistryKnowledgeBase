<?php

namespace DIQA\ChemExtension\Maintenance;

use DIQA\ChemExtension\Eval\EmbeddingClient;
use DIQA\ChemExtension\Eval\EvalLoopRunner;
use DIQA\ChemExtension\Eval\ExtractionScorer;
use DIQA\ChemExtension\Eval\GoldSetRepository;
use DIQA\ChemExtension\Eval\MoleculeResolver;
use DIQA\ChemExtension\Eval\ProseSimilarityScorer;
use DIQA\ChemExtension\Eval\TopicProfile;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\Title\Title;

/**
 * Load the required class
 */
if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Self-optimizing extraction-prompt loop.
 *
 * Runs the current extraction prompt for a topic against its gold set, scores the result
 * field-by-field, and iteratively asks the model to improve the prompt. The best prompt is
 * (optionally) written back to MediaWiki:Prompt_import_<Topic>.
 *
 * Requires $wgOpenAIKey. Gold set under <extension>/eval/<Topic>/ (see Eval\GoldSetRepository).
 *
 * Examples:
 *   php optimizeExtractionPrompt.php --topic Host_Guest_interaction --iterations 5 --dry-run
 *   php optimizeExtractionPrompt.php --topic Host_Guest_interaction --write
 */
class optimizeExtractionPrompt extends \Maintenance
{
    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Self-optimizing loop for topic extraction prompts (scored against the gold set)');
        $this->addOption('topic', 'Topic directory name under eval/ (e.g. Host_Guest_interaction)', true, true);
        $this->addOption('iterations', 'Number of optimization iterations (default 5)', false, true);
        $this->addOption('tolerance', 'Relative tolerance for numeric field comparison (default 0.1)', false, true);
        $this->addOption('token-penalty', 'Penalty per 1k tokens/publication when picking the best prompt (default 0 = pure F1)', false, true);
        $this->addOption('no-embeddings', 'Disable prose similarity (no embedding API calls)');
        $this->addOption('structured', 'Use structured outputs (JSON schema) instead of CSV-in-prose');
        $this->addOption('prompt-page', 'MediaWiki prompt page to seed from / write to (default Prompt_import_<Topic>)', false, true);
        $this->addOption('prompt-file', 'Seed the initial prompt from this file instead of the wiki page', false, true);
        $this->addOption('write', 'Write the best prompt back to the prompt page when finished');
        $this->addOption('dry-run', 'Do not write the prompt page (default behaviour)');
    }

    public function execute()
    {
        $topic = $this->getOption('topic');
        $iterations = (int) $this->getOption('iterations', 5);
        $tolerance = (float) $this->getOption('tolerance', 0.1);

        $promptPageName = $this->getOption('prompt-page', 'Prompt_import_' . $topic);
        $promptTitle = Title::newFromText($promptPageName, NS_MEDIAWIKI);

        $initialPrompt = $this->resolveInitialPrompt($promptTitle);
        if ($initialPrompt === '') {
            $this->fatalError("No initial prompt found. Provide --prompt-file or create MediaWiki:$promptPageName.");
        }

        $goldRepo = new GoldSetRepository();
        $available = $goldRepo->getTopics();
        if (!in_array($topic, $available, true)) {
            $this->fatalError("No gold set for topic '$topic'. Available: " . (empty($available) ? '(none)' : implode(', ', $available)));
        }

        // One topic-agnostic profile drives everything; it derives defaults from the wiki and is
        // refined per topic via eval/<topic>/profile.json.
        $profile = TopicProfile::forTopic($topic);

        $scorer = new ExtractionScorer(
            $tolerance,
            $profile->moleculeFields(),
            new MoleculeResolver(),
            $profile->expectedUnits()
        );
        $proseScorer = $this->hasOption('no-embeddings') ? null : new ProseSimilarityScorer(new EmbeddingClient());

        $runner = new EvalLoopRunner(
            $goldRepo,
            $scorer,
            null,
            null,
            fn($msg) => $this->output($msg . "\n"),
            $proseScorer
        );

        $runner->useSanityRules($profile->sanityRules());

        if ($this->hasOption('structured')) {
            $fields = $profile->fields();
            if (empty($fields)) {
                $this->fatalError("No experiment fields known for topic '$topic' — set 'form' or 'fields' in eval/$topic/profile.json.");
            }
            $runner->useStructuredOutput($profile->jsonSchema(), 'extraction_' . $topic);
            $this->output("Using structured outputs with " . count($fields) . " fields.\n");
        }

        $tokenPenalty = (float) $this->getOption('token-penalty', 0);
        $result = $runner->run($topic, $initialPrompt, $iterations, $tokenPenalty);

        $this->output("\n----------------------------------------\n");
        $this->output(sprintf("Best F1: %.4f\n", $result['best']['f1']));

        if ($this->hasOption('write') && !$this->hasOption('dry-run')) {
            WikiTools::doEditContent(
                $promptTitle,
                $result['best']['prompt'],
                'optimized extraction prompt (self-optimizing loop)',
                $promptTitle->exists() ? EDIT_UPDATE : EDIT_NEW
            );
            $this->output("Wrote best prompt to MediaWiki:$promptPageName\n");
        } else {
            $bestFile = $goldRepo->getTopicDir($topic) . '/best_prompt.txt';
            file_put_contents($bestFile, $result['best']['prompt']);
            $this->output("Dry run — best prompt written to $bestFile (not to the wiki).\n");
        }
    }

    private function resolveInitialPrompt(Title $promptTitle): string
    {
        $promptFile = $this->getOption('prompt-file');
        if ($promptFile !== null) {
            if (!is_file($promptFile)) {
                $this->fatalError("prompt-file not found: $promptFile");
            }
            return trim(file_get_contents($promptFile));
        }
        if ($promptTitle->exists()) {
            return trim(WikiTools::getText($promptTitle));
        }
        return '';
    }
}

$maintClass = optimizeExtractionPrompt::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
