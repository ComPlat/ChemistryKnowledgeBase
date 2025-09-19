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


        global $wgOpenAIPromptPages;
        $promptPage = $wgOpenAIPromptPages['publicationImport'] ?? 'Publication import prompt';
        $promptTitle = Title::newFromText($promptPage, NS_MEDIAWIKI);
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
        $result = $wikitextImporter->createInvestigationsFromCSV('Photocatalytic_CO2_conversion_experiments');
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

}
