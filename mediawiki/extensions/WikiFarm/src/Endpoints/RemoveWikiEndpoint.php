<?php

namespace DIQA\WikiFarm\Endpoints;


use DIQA\WikiFarm\WikiRepository;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;


/**
 * Endpoint to trigger the creation of a new virtual wiki
 */
class RemoveWikiEndpoint extends SimpleHandler {

    public function run() {
        global $wgUser;

        $params = $this->getValidatedParams();

        $lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
        $db = $lb->getConnection(DB_MASTER);
        (new WikiRepository($db))->removeWikiJob($params['wikiId'], $wgUser->getId());

        return ['result' => 'ok', 'wikiId' => $params['wikiId']];
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [

            'wikiId' => [
                self::PARAM_SOURCE => 'path',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],
        ];
    }
}