<?php
namespace DIQA\ChemExtension\Endpoints;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

class InvalidateExperimentCache extends SimpleHandler {

    public function run() {

        $jsonBody = $this->getRequest()->getBody();
        if (is_null($jsonBody) || $jsonBody == '') {
            $res = new Response("message body is empty");
            $res->setStatus(400);
            return $res;
        }
        $body = json_decode($jsonBody);
        if (!isset($body->cacheKeys)) {
            $res = new Response("cacheKeys property is missing");
            $res->setStatus(400);
            return $res;
        }

        $cache = MediaWikiServices::getInstance()->getMainObjectStash();
        foreach($body->cacheKeys as $cacheKey) {
            $cache->delete($cache->makeKey('investigation-link-table-data', $cacheKey));
        }

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