<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\MoleculeRenderer\MoleculeRendererClientImpl;
use Exception;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;

class RenderMolecule extends SimpleHandler
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
        if (!isset($body->molfile)) {
            $res = new Response("molfile property is missing");
            $res->setStatus(400);
            return $res;
        }
        try {
            $client = new MoleculeRendererClientImpl();
            return $client->render($body->molfile);
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