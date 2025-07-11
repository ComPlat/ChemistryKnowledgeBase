<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Job;
use Hooks;

class PublicationImportJob extends Job {

    private $path;
    private $logger;

    public function __construct( $title, $params ) {
        parent::__construct( 'PublicationImportJob', $title, $params );
        $this->path = $params['path'];
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
        $wikitext = "Imported from: " . $this->path;
        $wikitext .= "\n\n";

        $fileContent = file_get_contents($this->path);
        $wikitext .= $this->callAI($fileContent);

        $oldText = WikiTools::getText($this->getTitle());
        WikiTools::doEditContent($this->getTitle(), "$wikitext\n\n$oldText",
            "auto-generated",$this->getTitle()->exists() ? EDIT_UPDATE : EDIT_NEW);
    }

    private function callAI(string $fileContent): string
    {
        // TODO: call AI
        return '- This is created by AI -';
    }


}
