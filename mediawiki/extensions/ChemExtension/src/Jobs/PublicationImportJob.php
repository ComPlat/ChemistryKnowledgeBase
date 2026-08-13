<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\PublicationImport\ExperimentWikitextImporter;
use DIQA\ChemExtension\PublicationImport\ImportProcessRepository;
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
    private $repo;

    public function __construct($title, $params)
    {
        parent::__construct('PublicationImportJob', $title, $params);
        $this->paths = $params['paths'];
        $this->doi = $params['doi'];
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
        try {

            register_shutdown_function([$this, 'cleanup']);
            set_error_handler([$this, 'cleanup']);      // non-fatal errors
            set_exception_handler([$this, 'cleanup']);  // uncatched throwables

            if (!WikiTools::createNotificationJobs($this->getTitle())) {
                $this->logger->warn("Notification job was not created for page: " . $this->getTitle()->getPrefixedText());
            }

            $process = $this->repo->getImportProcessByDOI($this->doi);

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
        }
    }

    private function cleanup(): void
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
        $this->repo->markAsFailed($process['id']);
    }

    private function importPublicationPage()
    {
        $doi = $this->doi;
        $importNotice = "Imported from: " . join(', ', $this->paths);

        $topicsCategoryAnnotations = join("\n", array_map(function ($topic) {
            return "[[Category:$topic]]";
        }, $this->topics));


        $promptTitle = $this->findSuitablePrompt();
        if (!$promptTitle->exists()) {
            // fallback
            $prompt = "Can you tell me what the document is about?";
        } else {
            $prompt = WikiTools::getText($promptTitle);
        }
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
        WikiTools::doEditContent($this->getTitle(), $wikitext,
            "auto-generated", $this->getTitle()->exists() ? EDIT_UPDATE : EDIT_NEW);

        $aiClient->deleteFiles($fileIds);
    }

    private function findSuitablePrompt(): Title
    {
        global $wgOpenAIPromptPages;
        $promptPage = $wgOpenAIPromptPages['publicationImport'] ?? 'Publication import prompt';
        $promptTitle = Title::newFromText($promptPage, NS_MEDIAWIKI);

        // OR choose the first topic that has a prompt
        foreach($this->topics as $topic) {
            $title = Title::newFromText("Prompt import " . $topic, NS_MEDIAWIKI);
            if ($title->exists()) {
                $promptTitle = $title;
                break;
            }
        }

        return $promptTitle;
    }

}
