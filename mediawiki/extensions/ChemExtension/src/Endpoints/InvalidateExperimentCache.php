<?php
namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\ParserFunctions\ExperimentLink;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;

class InvalidateExperimentCache extends SimpleHandler {

    public function run() {

        $jsonBody = $this->getRequest()->getBody();
        if (is_null($jsonBody) || $jsonBody == '') {
            $res = new Response("message body is empty");
            $res->setStatus(400);
            return $res;
        }
        $body = json_decode($jsonBody);
        if (!isset($body->cacheKey)) {
            $res = new Response("cacheKeys property is missing");
            $res->setStatus(400);
            return $res;
        }

        $cache = MediaWikiServices::getInstance()->getMainObjectStash();
        $cache->delete($cache->makeKey('investigation-link-table-data', $body->cacheKey));

        $result = ExperimentLink::getContentFromCache([
            'form' => $body->parameters->form,
            'description' =>$body->parameters->description,
            'restrictToPages' => $body->parameters->restrictToPages,
            'sort' => $body->parameters->sort,
            'order' => $body->parameters->order,
        ], $body->selectExperimentQuery, \Title::newFromText($body->page));

        return ['html' => $result];
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [];
    }
}