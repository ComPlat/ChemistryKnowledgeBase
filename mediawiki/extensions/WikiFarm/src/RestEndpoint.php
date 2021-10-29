<?php

namespace DIQA\WikiFarm;


use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;


/**
 * solr proxy REST endpoint. This is where SOLR requests are processed
 */
class RestEndpoint extends SimpleHandler {

    public function run() {
        global $wgUser;

        $params = $this->getValidatedParams();

        $lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
        $db = $lb->getConnection(DB_MASTER);
        $wikiId = (new WikiRepository($db))->createWikiJob($params['wikiName'], $wgUser->getName());

        return ['result' => 'ok', 'wikiId' => $wikiId];
    }

    public function needsWriteAccess() {
        return false;
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