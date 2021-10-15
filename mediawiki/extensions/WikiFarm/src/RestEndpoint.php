<?php

namespace DIQA\WikiFarm;


use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;


/**
 * solr proxy REST endpoint. This is where SOLR requests are processed
 */
class RestEndpoint extends SimpleHandler {

    public function run() {
        $params = $this->getValidatedParams();
        $jobParams = [ 'wiki' => $params['wikiId'], 'name' => $params['wikiName'] ];
        $title = \Title::newFromText( "Wiki {$params['wikiName']}/CreateWikiJob" );
        $job = new CreateWikiJob( $title, $jobParams );
        \JobQueueGroup::singleton()->push( $job );

        return ['result' => 'ok'];
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [
            'wikiId' => [
                self::PARAM_SOURCE => 'post',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],
            'wikiName' => [
                self::PARAM_SOURCE => 'post',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],
        ];
    }
}