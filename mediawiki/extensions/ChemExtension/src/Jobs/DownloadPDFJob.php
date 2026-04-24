<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\PdfUtils;
use Job;

class DownloadPDFJob extends Job {

    private $logger;

    public function __construct($title, $params)
    {
        parent::__construct('DownloadPDFJob', $title, $params);
        $this->logger = new LoggerUtils('DownloadPDFJob', 'ChemExtension');
    }

    public function run()
    {
        $params = $this->getParams();
        $url = $params['url'];
        $doi = $params['doi'];
        $this->logger->debug('Loading from URL: ' . $url);

        global $wgChemPubStoreDir;
        if (!isset($wgChemPubStoreDir)) {
            $wgChemPubStoreDir = sys_get_temp_dir();
            $this->logger->error('$wgChemPubStoreDir is not set. using system tmp-dir as default');
        }
        $tmpFile = $wgChemPubStoreDir . "/" . md5($doi) . '.pdf';
        $cmdParams = " --url=" . escapeshellarg($url);
        $cmdParams .= " --dir=".escapeshellarg($tmpFile);
        global $wgChemChromeBin, $wgChemChromeDriverBin, $wgChemChromeDriverLog;
        if (!isset($wgChemChromeBin)) {
            $this->logger->error('$wgChemChromeBin is not set');
        } else {
            $cmdParams .= " --chromebin=".escapeshellarg($wgChemChromeBin);
        }
        if (!isset($wgChemChromeDriverBin)) {
            $this->logger->error('$wgChemChromeDriverBin is not set');
        } else {
            $cmdParams .= " --chromedriver=".escapeshellarg($wgChemChromeDriverBin);
        }
        if (isset($wgChemChromeDriverLog)) {
            $cmdParams .= " --logfile=".escapeshellarg($wgChemChromeDriverLog);
            $this->logger->log('$wgChemChromeDriverLog is not set');
        }

        $output = shell_exec("java -jar /opt/downloadPDF/downloadPDF.jar $cmdParams 2>&1");
        if (file_exists($tmpFile) && !PdfUtils::isPdfFile($tmpFile)) {
            $this->logger->debug('Not a PDF file: ' . $tmpFile. ". Deleted.");
            unlink($tmpFile);
        }
        $this->logger->debug($output);
        
    }
}