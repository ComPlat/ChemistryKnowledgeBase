<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\PublicationImport\ExperimentWikitextImporter;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Job;
use Hooks;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class PublicationImportJob extends Job
{

    private $paths;
    private $doi;
    private $topics;
    private $logger;

    public function __construct($title, $params)
    {
        parent::__construct('PublicationImportJob', $title, $params);
        $this->paths = $params['paths'];
        $this->doi = $params['doi'];
        $this->topics = $params['topics'];
        $this->logger = new LoggerUtils('PublicationImportJob', 'ChemExtension');
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        try {

            if (!WikiTools::createNotificationJobs($this->getTitle())) {
                $this->logger->warn("Notification job was not created for page: " . $this->getTitle()->getPrefixedText());
            }
            $this->importPublicationPage();
            $hooksContainer = MediaWikiServices::getInstance()->getHookContainer();
            $hooksContainer->run('CleanupChemExtState');

        } catch (Exception $e) {
            $this->logger->error("ERROR: " . $e->getMessage());
        }
    }

    private function importPublicationPage()
    {
        $doi = $this->doi;
        $importNotice = "Imported from: " . join(', ', $this->paths);

        $topicsCategoryAnnotations = join("\n", array_map(function ($topic) {
            return "[[Category:$topic]]";
        }, $this->topics));


        $prompt = $this->resolveImportPrompt();
        $this->logger->log("prompt for AI: " . $prompt);

        $aiClient = new AIClient();
        $fileIds = $aiClient->uploadFiles($this->paths);

        $aiText = $aiClient->callAI($fileIds, $prompt);

        $wikitext = <<<WIKITEXT
$importNotice

{{BaseTemplate}}
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
        $oldText = WikiTools::getText($this->getTitle());
        WikiTools::doEditContent($this->getTitle(), "$wikitext\n\n$oldText",
            "auto-generated", $this->getTitle()->exists() ? EDIT_UPDATE : EDIT_NEW);

        $aiClient->deleteFiles($fileIds);
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
