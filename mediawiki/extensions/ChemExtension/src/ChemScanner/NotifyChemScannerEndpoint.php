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
        $body = $this->getRequest()->getBody()->getContents();
        parse_str($body, $queryParams);

        $jobId = $queryParams['job_id'] ?? null;
        if (is_null($jobId)) {
            $res = new Response("job_id query parameter is required");
            $res->setStatus(400);
            $logger->error("Bad request: job_id query parameter is required");
            return $res;
        }

        try {
            $this->createChemScannerImportJob($jobId, $queryParams['data']);
            $logger->debug(sprintf("ChemScannerImportJob created with job_id: %s, data: %s", $queryParams['job_id'], $queryParams['data']));
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

        $jobParams = [];
        $jobParams['body'] = $body;
        $jobParams['job_id'] = $jobId;
        $job = new ChemScannerImportJob($title, $jobParams);
        JobQueueGroup::singleton()->push($job);
    }

    public function needsReadAccess()
    {
        return false;
    }

}