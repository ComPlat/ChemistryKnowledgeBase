<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\Utils\LoggerUtils;
use Job;
use MediaWiki\MediaWikiServices;

class PageImportJob extends Job {

    private $logger;

    public function __construct($title, $params)
    {
        parent::__construct('PageImportJob', $title, $params);
        $this->logger = new LoggerUtils('PageImportJob', 'ChemExtension');
    }

    public function run()
    {
        $o = new \stdClass();
        $params = $this->getParams();
        $o->wikiText = $params['wikitext'];
        $title = escapeshellarg($this->getTitle()->getDBkey());
        $this->logger->debug('Importing page: ' . $this->getTitle()->getDBkey());

        // save wiki in tmp file
        $tmpFile = sys_get_temp_dir() . "/" . uniqid();
        file_put_contents($tmpFile, json_encode($o));

        // call import script in main context
        global $wgWikiFarmBinFolder, $IP;
        $wgWikiFarmBinFolder = $wgWikiFarmBinFolder ?? "$IP/../bin";
        chdir($wgWikiFarmBinFolder);
        print "\nbash $wgWikiFarmBinFolder/runImportInMainContext.sh $tmpFile $title 2>&1";
        $output = shell_exec("bash $wgWikiFarmBinFolder/runImportInMainContext.sh $tmpFile $title 2>&1");
        $this->logger->debug($output);
        $hooksContainer = MediaWikiServices::getInstance()->getHookContainer();
        $hooksContainer->run('CleanupChemExtState');
    }
}