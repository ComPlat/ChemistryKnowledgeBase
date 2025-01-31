<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\ParserFunctions\ExperimentLink;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;

class InvalidateExperimentListCache extends SimpleHandler {

    public function run() {

        $jsonBody = $this->getRequest()->getBody();
        if (is_null($jsonBody) || $jsonBody == '') {
            $res = new Response("message body is empty");
            $res->setStatus(400);
            return $res;
        }
        $body = json_decode($jsonBody);
        if (!isset($body->cacheKey)) {
            $res = new Response("cacheKey property is missing");
            $res->setStatus(400);
            return $res;
        }

        $cache = MediaWikiServices::getInstance()->getMainObjectStash();
        $cache->delete($cache->makeKey('investigation-table', $body->cacheKey));

        $res = new Response();
        $res->setStatus(200);
        return $res;
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [];
    }
}