<?php
namespace DIQA\ChemExtension\Endpoints;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class InvalidateExperimentCache extends SimpleHandler {

    public function run() {

        $params = $this->getValidatedParams();

        $cache = MediaWikiServices::getInstance()->getMainObjectStash();
        $cache->delete($cache->makeKey('investigation-table-data-2', $params['cacheKey']));

        $res = new Response();
        $res->setStatus(200);
        return $res;
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [
            'cacheKey' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],

        ];
    }
}