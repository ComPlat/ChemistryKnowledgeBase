<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\Utils\LoggerUtils;
use Job;

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

        // save wiki in tmp file
        $tmpFile = sys_get_temp_dir() . "/" . uniqid();
        file_put_contents($tmpFile, json_encode($o));

        // call import script in main context
        global $wgWikiFarmBinFolder, $IP;
        $wgWikiFarmBinFolder = $wgWikiFarmBinFolder ?? "$IP/../bin";
        chdir($wgWikiFarmBinFolder);
        print "\nbash $wgWikiFarmBinFolder/runImportInMainContext.sh $tmpFile $title 2>&1";
        echo shell_exec("bash $wgWikiFarmBinFolder/runImportInMainContext.sh $tmpFile $title 2>&1");
    }
}