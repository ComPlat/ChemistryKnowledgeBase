<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Job;
use Hooks;

class PublicationImportJob extends Job {

    private $paths;
    private $doi;
    private $topics;
    private $logger;

    public function __construct( $title, $params ) {
        parent::__construct( 'PublicationImportJob', $title, $params );
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
                $this->logger->warn("Notification job was not created for page: " .$this->getTitle()->getPrefixedText());
            }
            $this->importPublicationPage();
            Hooks::run('CleanupChemExtState');

        } catch (Exception $e) {
            $this->logger->error("ERROR: " . $e->getMessage());
        }
    }

    private function importPublicationPage() {
        $wikitext = "Imported from: " . join(', ', $this->paths);
        $wikitext .= "\n\n";

        $fileContent = '';
        foreach($this->paths as $path) {
            $fileContent .= file_get_contents($path);
        }
        $wikitext .= $this->callAI($fileContent);

        $oldText = WikiTools::getText($this->getTitle());
        WikiTools::doEditContent($this->getTitle(), "$wikitext\n\n$oldText",
            "auto-generated",$this->getTitle()->exists() ? EDIT_UPDATE : EDIT_NEW);
    }

    private function callAI(string $fileContent): string
    {
        $result = "";
        try {
            $aiClient = new AIClient();
            // TODO: call AI
            //$wikitext = $aiClient->getData($fileContent);
            $wikitext = "\n- This is created by AI -";
            $result .= "\nDOI: " . $this->doi;
            $result .= "\nTopcis: " . join(',', $this->topics);
            $result .= $wikitext;
            return $result;
        } catch (Exception $e) {
            $result .= "Error on import: " . $e->getMessage();
        }
        return $result;
    }


}
