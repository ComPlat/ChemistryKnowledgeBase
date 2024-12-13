<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\MoleculeRenderer\MoleculeRendererClientImpl;
use Exception;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Title;
use RequestContext;

class RenamePage extends SimpleHandler
{

    public function run()
    {

        $jsonBody = $this->getRequest()->getBody();

        if (is_null($jsonBody) || $jsonBody == '') {
            $res = new Response("message body is empty");
            $res->setStatus(400);
            return $res;
        }
        $body = json_decode($jsonBody);
        if (!isset($body->oldPageTitle) || !isset($body->newPageTitle)) {
            $res = new Response("oldTitle or newTitle is missing");
            $res->setStatus(400);
            return $res;
        }
        try {

            $movePage = MediaWikiServices::getInstance()->getMovePageFactory()->newMovePage(
                Title::newFromText($body->oldPageTitle),
                Title::newFromText($body->newPageTitle)
            );
            $movePage->moveIfAllowed(RequestContext::getMain()->getUser());
            $res = new Response();
            $res->setStatus(200);
            return $res;
        } catch (Exception $e) {
            $res = new Response($e->getMessage());
            $res->setStatus(500);
            return $res;
        }

    }

    public function needsWriteAccess()
    {
        return false;
    }

    public function getParamSettings()
    {
        return [];
    }
}