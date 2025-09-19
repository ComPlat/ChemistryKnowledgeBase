<?php
namespace DIQA\WikiFarm\Endpoints;

use DIQA\WikiFarm\WikiRepository;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;
use MediaWiki\Rest\Response;
use User;

class UpdateUserOfWiki extends SimpleHandler {

    public function run() {

        $params = $this->getValidatedParams();
        $jsonBody = $this->getRequest()->getBody();
        $body = json_decode($jsonBody);
        $users = $body->users ?? NULL;

        if (is_null($users)) {
            $res = new Response("field 'users' is required in body");
            $res->setStatus(400);
            return $res;
        }
        $lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
        $db = $lb->getConnection(DB_PRIMARY);
        $userObjects = array_map(function($e) { return User::newFromName($e);}, $users);
        $wikiRepository = new WikiRepository($db);
        $wikiRepository->addUserToWiki($userObjects, $params['wikiId'], WikiRepository::USER);

        return new Response();
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