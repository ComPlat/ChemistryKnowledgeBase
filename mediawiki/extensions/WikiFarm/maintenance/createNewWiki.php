<?php

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

class createNewWiki extends Maintenance
{


    public function __construct()
    {
        parent::__construct();
        $this->addDescription("Creates a new Wiki in this Wiki farm");
        $this->addOption('config', 'name of a JSON-File that contains wiki config', false, true, 'c');
        $this->addOption('rollback', 'rollback the changes of given wiki-ID, including dropping all data from the database', false, true, 'r');
    }

    public function execute()
    {
       $wikiGenerator = new DIQA\WikiFarm\WikiGenerator\WikiGenerator();

       try {
           if ($this->hasOption('rollback')) {
               global $wgDBpassword;
               if(!$this->hasOption('dbpass')) {
                   echo "ERROR: Please add the DB password in the option --dbpass to make sure you know what you are doing.\n";
                   return;
               } else if($this->getOption('dbpass', '') != $wgDBpassword) {
                   echo "ERROR: Please provide the correct DB password.\n";
                   return;
               }
               echo "\n== Starting Rollback ==\n\n";
               $wikiGenerator->rollback($this->getOption('rollback'));
               echo "\nFinished Rollback.\n";
           } else if ($this->hasOption('config')) {
               echo "\n== Creating new Wiki ==\n\n";
               $wikiGenerator->createNewWikiFromFile($this->getOption('config'));
               echo "\nFinished Creation of new Wiki.\n";
           } else {
               echo "ERROR: Either option --config or --rollback is required.\n";
               $this->maybeHelp(true);
           }
       } catch(Exception $e) {
           echo "ERROR: ".$e->getMessage();
           echo "\n";
       }
    }

}

$maintClass = "createNewWiki";
require_once RUN_MAINTENANCE_IF_MAIN;
