<?php

namespace DIQA\ChemExtension\Chemscanner;


use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Job;

class ChemScannerImportJob extends Job {

    private $jobId;
    private $body;
    private $logger;

    public function __construct( $title, $params ) {
        parent::__construct( 'ChemScannerImportJob', $title, $params );
        $this->jobId = $params['job_id'];
        $this->body = $params['body'];
        $this->logger = new LoggerUtils('ChemScannerImportJob', 'ChemExtension');
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        try {

            $chemScannerResult = json_decode($this->body);
            $this->importChemScannerResult($chemScannerResult);

            WikiTools::createNotificationJobs($this->getTitle());

        } catch (Exception $e) {
            $this->logger->error("ERROR: " . $e->getMessage());

        }
    }

    private function importChemScannerResult($chemScannerResult) {
        // TODO: Implement import from $chemScannerResult
        WikiTools::doEditContent($this->getTitle(), "real data", "auto-generated");
    }
}
