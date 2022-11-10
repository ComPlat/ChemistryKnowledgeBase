<?php
namespace DIQA\ChemExtension\Endpoints;

use Exception;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class IsJobPending extends SimpleHandler {

    public function run() {

        $params = $this->getValidatedParams();

        $pageId = $params['pageId'];

        try {

            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
            $res = $dbr->select(['job', 'page'], ['job_id'],
                [
                    'page.page_id' => $pageId,
                    'page.page_namespace = job.job_namespace', 'page.page_title = job.job_title',
                    'job.job_attempts' => 0
                ]);
            return [ 'jobPending' => ($res->numRows() > 0) ];

        } catch(Exception $e) {
            $res = new Response($e->getMessage());
            $res->setStatus(500);
            return $res;
        }
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [

            'pageId' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ]
        ];
    }
}