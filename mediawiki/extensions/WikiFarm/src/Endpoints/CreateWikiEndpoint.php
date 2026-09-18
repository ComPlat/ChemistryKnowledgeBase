<?php

namespace DIQA\WikiFarm\Endpoints;


use DIQA\WikiFarm\CreateWikiJob;
use DIQA\WikiFarm\WikiRepository;
use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Title\Title;
use Wikimedia\ParamValidator\ParamValidator;


/**
 * Endpoint to trigger the creation of a new virtual wiki
 */
class CreateWikiEndpoint extends SimpleHandler {

    public function run() {

        $params = $this->getValidatedParams();

        $lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
        $user = RequestContext::getMain()->getUser();
        $db = $lb->getConnection(DB_PRIMARY);
        $wikiRepository = new WikiRepository($db);
        $wikiName = $params['wikiName'];
        $anonymous = MediaWikiServices::getInstance()
            ->getUserFactory()
            ->newAnonymous();
        $wikiId = $wikiRepository->createWikiInDB($wikiName, $user ?? $anonymous);
        $title = Title::newFromText( "Wiki $wikiName/CreateWikiJob" );
        $jobParams = [ 'wikiName' => $wikiName, 'wikiId' => "$wikiId" ];
        $job = new CreateWikiJob( $title, $jobParams );
        $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
        $jobQueue->push( $job );
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