<?php

namespace DIQA\ChemExtension\ChemScanner;

use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;
use JobQueueGroup;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use Title;

class NotifyChemScannerEndpoint extends Handler
{

    /**
     * Handles the request to notify that chemscanner job has finished
     * @throws \MWException
     */
    public function execute()
    {
        $logger = new LoggerUtils('NotifyChemScannerEndpoint', 'ChemExtension');
        $queryParams = $this->getRequest()->getQueryParams();
        $logger->log("Request: " . $this->getRequest()->getUri()->getQuery());

        $jobId = $queryParams['job_id'] ?? null;
        if (is_null($jobId)) {
            $res = new Response("job_id query parameter is required");
            $res->setStatus(400);
            $logger->error("Bad request: job_id query parameter is required");
            return $res;
        }

        try {
            $body = $this->getRequest()->getBody()->getContents();
            $this->createChemScannerImportJob($jobId, $body);
            return new Response();
        } catch (Exception $e) {
            $res = new Response($e->getMessage());
            $res->setStatus(400);
            $logger->error($e->getMessage());
            return $res;
        }
    }

    /**
     * @throws \Exception
     */
    private function createChemScannerImportJob($jobId, $body)
    {
        $title = Title::newFromText($jobId);
        if (!$title->exists()) {
            throw new Exception("No according page found for this job_id: $jobId");
        }
        $jobParams = [];
        $jobParams['body'] = $body;
        $job = new ChemScannerImportJob($title, $jobParams);
        JobQueueGroup::singleton()->push($job);
    }

    public function needsReadAccess()
    {
        return false;
    }

}