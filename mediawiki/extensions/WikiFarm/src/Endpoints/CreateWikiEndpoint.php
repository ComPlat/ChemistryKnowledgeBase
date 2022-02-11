<?php

namespace DIQA\WikiFarm\Endpoints;


use DIQA\WikiFarm\WikiRepository;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;


/**
 * Endpoint to trigger the creation of a new virtual wiki
 */
class CreateWikiEndpoint extends SimpleHandler {

    public function run() {
        global $wgUser;

        $params = $this->getValidatedParams();

        $lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
        $db = $lb->getConnection(DB_MASTER);
        $wikiId = (new WikiRepository($db))->createWikiJob($params['wikiName'], $wgUser->getName());

        return ['result' => 'ok', 'wikiId' => $wikiId];
    }

    public function getParamSettings() {
        return [

            'wikiName' => [
                self::PARAM_SOURCE => 'post',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],
        ];
    }
}