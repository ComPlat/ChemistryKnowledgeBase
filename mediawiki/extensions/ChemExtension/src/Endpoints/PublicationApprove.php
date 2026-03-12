<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\MoleculeRenderer\MoleculeRendererClientImpl;
use DIQA\ChemExtension\PublicationSearch\PublicationSearchRepository;
use Exception;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Title;
use RequestContext;

class PublicationApprove extends SimpleHandler
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
        if (!isset($body->doi) || !isset($body->isApproved)) {
            $res = new Response("doi or isApproved is missing");
            $res->setStatus(400);
            return $res;
        }

        try {
            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
            $publicationRepo = new PublicationSearchRepository($dbr);

            $publicationRepo->updateApproved($body->doi, $body->isApproved);

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
        return true;
    }

    public function getParamSettings()
    {
        return [];
    }
}