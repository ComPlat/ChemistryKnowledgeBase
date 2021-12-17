<?php
namespace DIQA\ChemExtension\ChemScanner;

use JobQueueGroup;
use MediaWiki\MediaWikiServices;
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

        $queryParams = $this->getRequest()->getQueryParams();
        $title = $queryParams['title'] ?? null;
        if (is_null($title)) {
            $res = new Response("title query parameter is required");
            $res->setStatus(400);
            return $res;
        }
        $body = $this->getRequest()->getBody()->getContents();
        $this->createChemScannerImportJob($title, $body);
        return new Response();
    }

    private function createChemScannerImportJob($title, $body) {
        $title = Title::newFromText( $title );
        $jobParams = [];
        $jobParams['body'] = $body;
        $job = new ChemScannerImportJob( $title, $jobParams );
        JobQueueGroup::singleton()->push( $job );
    }

    public function needsReadAccess() {
        return false;
    }

}