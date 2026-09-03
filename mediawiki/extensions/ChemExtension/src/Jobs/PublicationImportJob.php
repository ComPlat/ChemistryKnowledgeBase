<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\PublicationImport\EnsembleExtractor;
use DIQA\ChemExtension\PublicationImport\ExperimentWikitextImporter;
use DIQA\ChemExtension\PublicationImport\ImportProcessRepository;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Job;
use Hooks;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class PublicationImportJob extends Job
{

    private $paths;
    private $doi;
    private $displayTitle;
    private $importProcessId;
    private $topics;
    private $logger;
    private $repo;

    public function __construct($title, $params)
    {
        parent::__construct('PublicationImportJob', $title, $params);
        $this->paths = $params['paths'];
        $this->doi = $params['doi'];
        $this->displayTitle = $params['displayTitle'];
        $this->importProcessId = $params['importProcessId'];;
        $this->topics = $params['topics'];
        $this->logger = new LoggerUtils('PublicationImportJob', 'ChemExtension');
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $this->repo = new ImportProcessRepository($dbr);
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        $process = $this->repo->getImportProcess($this->importProcessId);

        try {

            register_shutdown_function([$this, 'cleanup']);
            set_error_handler([$this, 'cleanup']);      // non-fatal errors
            set_exception_handler([$this, 'cleanup']);  // uncatched throwables

            if (!WikiTools::createNotificationJobs($this->getTitle())) {
                $this->logger->warn("Notification job was not created for page: " . $this->getTitle()->getPrefixedText());
            }


            $lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
            $this->repo->markAsRunning($process['id']);

            $lbFactory->commitPrimaryChanges( __METHOD__ );
            $lbFactory->waitForReplication();
            $lbFactory->beginPrimaryChanges( __METHOD__ );

            $this->importPublicationPage();

            $this->repo->markAsFinished($process['id']);
            $hooksContainer = MediaWikiServices::getInstance()->getHookContainer();
            $hooksContainer->run('CleanupChemExtState');

        } catch (Exception $e) {
            $this->logger->error("ERROR: " . $e->getMessage());
            $this->repo->markAsFailed($process['id'], $e->getMessage());
        }
    }

    public function cleanup(): void
    {

        $lastError = error_get_last();

        if ($lastError === null) {
            return; // Normal shutdown, no error
        }

        // Only act on real fatal errors
        $fatalTypes = [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_CORE_WARNING,
            E_COMPILE_ERROR,
            E_COMPILE_WARNING,
        ];

        if (!in_array($lastError['type'], $fatalTypes, true)) {
            return;
        }

        $process = $this->repo->getImportProcessByDOI($this->doi);
        $this->repo->markAsFailed($process['id'], $lastError['message']);
    }

    private function importPublicationPage()
    {
        $doi = $this->doi;
        $pathItems = implode('', array_map(static function ($path) {
            return Html::rawElement('li', [], htmlspecialchars($path));
        }, $this->paths));
        $importNotice = Html::rawElement("div", ['class' => 'ce_import_notice'],
            "Imported from: " . Html::rawElement('ul', [], $pathItems));

        $topicsCategoryAnnotations = join("\n", array_map(function ($topic) {
            return "[[Category:$topic]]";
        }, $this->topics));


        $prompt = $this->resolveImportPrompt();
        $this->logger->log("prompt for AI: " . $prompt);

        $aiClient = AIClient::getAIClient();
        $fileIds = $aiClient->uploadFiles($this->paths);

        // Consistency wrapper: if $wgAIExtractionPasses > 1, run the extractor N times in
        // parallel and merge deterministically (row-union + cell-majority). Same PDF/prompt
        // → same wiki page across re-imports. N=1 (default) preserves legacy behaviour.
        $ensemble = new EnsembleExtractor($aiClient);
        $aiText = $ensemble->extract($fileIds, $prompt);

        $reviewNotice = $this->reviewNoticeIfLowConfidence($aiClient, $fileIds, $aiText);

        $wikitext = <<<WIKITEXT
$importNotice
$reviewNotice
{{BaseTemplate}}
{{DISPLAYTITLE: $this->displayTitle }}
{{DOI|doi=$doi}}

$aiText

$topicsCategoryAnnotations
WIKITEXT;

        $wikitextImporter = new ExperimentWikitextImporter($wikitext);
        $result = $wikitextImporter->createInvestigationsFromCSV();
        $wikitext = $result['wikitext'];
        foreach($result['investigationPages'] as $page => $content) {
            $invPage = Title::newFromText($this->getTitle()->getPrefixedText() . "/$page");
            WikiTools::doEditContent($invPage->getPrefixedText(), $content,
                "auto-generated", $invPage->exists() ? EDIT_UPDATE : EDIT_NEW);
            $this->logger->log("created investigation page: " . $invPage->getPrefixedText());
        }

        $this->logger->log("generated text from AI: " . $wikitext);
        WikiTools::doEditContent($this->getTitle(), $wikitext,
            "auto-generated", $this->getTitle()->exists() ? EDIT_UPDATE : EDIT_NEW);

        $aiClient->deleteFiles($fileIds);
    }

    /**
     * Optional confidence gate: when $wgCEExtractionCriticThreshold is configured, a second-pass
     * critic verifies the extracted experiments against the source. If the average confidence is
     * below the threshold, returns a review notice (+ category) to prepend to the page so a human
     * checks it instead of trusting the auto-import silently. Returns '' when disabled or healthy.
     */
    private function reviewNoticeIfLowConfidence(AIClient $aiClient, array $fileIds, string $aiText): string
    {
        global $wgCEExtractionCriticThreshold;
        if (!isset($wgCEExtractionCriticThreshold)) {
            return '';
        }
        $threshold = (float) $wgCEExtractionCriticThreshold;
        try {
            $rows = \DIQA\ChemExtension\Eval\CsvExtractionParser::parseRows($aiText);
            if (empty($rows)) {
                return '';
            }
            $critic = new \DIQA\ChemExtension\Eval\ExtractionCritic($aiClient, $threshold);
            $review = $critic->reviewWithFiles($fileIds, $rows);
            if ($review['avgConfidence'] !== null && $review['avgConfidence'] < $threshold) {
                $this->logger->log("Extraction flagged for review (confidence {$review['avgConfidence']})");
                return sprintf(
                    "'''\u{26a0} Auto-extraction flagged for review''' (critic confidence %.2f, %d low-confidence row(s)).\n[[Category:Needs review]]\n",
                    $review['avgConfidence'],
                    count($review['flagged'])
                );
            }
        } catch (Exception $e) {
            $this->logger->warn("Critic check failed: " . $e->getMessage());
        }
        return '';
    }

    /**
     * Resolves the extraction prompt for this publication.
     *
     * Resolution order (first match wins):
     *  1. Topic-specific prompt page MediaWiki:Prompt_import_<Topic> for any assigned topic.
     *     This mirrors the topic-specific *search* prompts (MediaWiki:Prompt_<Topic>) used in
     *     {@see CrossRefSearchJob} and lets each topic request the CSV columns that match its
     *     own investigation template (e.g. Ka/Kd for Host-Guest, λexc/TON for photocatalysis).
     *  2. The configurable global import prompt ($wgOpenAIPromptPages['publicationImport'],
     *     default 'Publication import prompt') — the previous behaviour.
     *  3. A hard-coded fallback question.
     *
     * Prompts are read as raw wikitext (WikiTools::getText) to preserve the exact formatting
     * the model relies on (section layout, CSV table skeleton).
     */
    private function resolveImportPrompt(): string
    {
        foreach ($this->topics as $topic) {
            $topic = trim($topic);
            if ($topic === '' || $topic === 'Topic') {
                continue;
            }
            $topicPromptTitle = Title::newFromText('Prompt_import_' . $topic, NS_MEDIAWIKI);
            if ($topicPromptTitle !== null && $topicPromptTitle->exists()) {
                $this->logger->log("Using topic-specific import prompt for topic: $topic");
                return WikiTools::getText($topicPromptTitle);
            }
        }

        global $wgOpenAIPromptPages;
        $promptPage = $wgOpenAIPromptPages['publicationImport'] ?? 'Publication import prompt';
        $promptTitle = Title::newFromText($promptPage, NS_MEDIAWIKI);
        if ($promptTitle !== null && $promptTitle->exists()) {
            return WikiTools::getText($promptTitle);
        }

        // fallback
        return "Can you tell me what the document is about?";
    }

}
