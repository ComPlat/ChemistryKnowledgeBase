<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Job;
use Hooks;

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
            Hooks::run('CleanupChemExtState');

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


        global $wgOpenAIPrompt;
        $promptDir = __DIR__ . "/../../resources/ai-prompts/";
        $prompt = file_get_contents($promptDir . $wgOpenAIPrompt ?? "resume.txt");

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

        print "\nimportnotice:".$importNotice;
        print "\naitext:".$aiText;
        print "\ntopics:".$topicsCategoryAnnotations;
        print_r($wikitext);
        $oldText = WikiTools::getText($this->getTitle());
        WikiTools::doEditContent($this->getTitle(), "$wikitext\n\n$oldText",
            "auto-generated", $this->getTitle()->exists() ? EDIT_UPDATE : EDIT_NEW);
    }

}
