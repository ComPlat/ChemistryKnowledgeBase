<?php
namespace DIQA\ChemExtension;

use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

class NotifyChemscannerJob extends Handler
{

    /**
     * Handles the request to notify that chemscanner job has finished
     * @throws \MWException
     */
    public function execute()
    {
        $jobId = $this->getRequest()->getPathParam('job_id');

        if (is_null($jobId)) {
            $res = new Response("job-id parameter is required");
            $res->setStatus(400);
            return $res;
        }
        return new Response();
    }

    public function needsReadAccess() {
        return false;
    }

}