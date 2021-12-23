<?php
namespace DIQA\WikiFarm\Endpoints;

use DIQA\WikiFarm\WikiRepository;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Philo\Blade\Blade;
use Wikimedia\ParamValidator\ParamValidator;

class GetWikiUsersEndpoint extends SimpleHandler {

    public function run() {

        $params = $this->getValidatedParams();
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $repository = new WikiRepository($dbr);
        $usersOfWiki = $repository->getAllUsersOfWiki($params['wikiId']);

        return ['users' => array_map(function($e) { return $e->getName(); }, $usersOfWiki)];
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