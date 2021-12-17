<?php

namespace DIQA\ChemExtension\Chemscanner;


use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Job;

class ChemScannerImportJob extends Job {

    private $jobId;
    private $body;


    public function __construct( $title, $params ) {
        parent::__construct( 'ChemScannerImportJob', $title, $params );
        $this->jobId = $params['job_id'];
        $this->body = $params['body'];
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
            wfDebugLog("ChemScannerJob", "\nERROR: " . $e->getMessage());

        }
    }

    private function importChemScannerResult($chemScannerResult) {
        // TODO: Implement import from $chemScannerResult
        WikiTools::doEditContent($this->getTitle(), "real data", "auto-generated");
    }
}
