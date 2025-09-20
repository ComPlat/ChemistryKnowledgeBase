<?php

namespace DIQA\FacetedSearch2\Endpoints;

use DIQA\FacetedSearch2\ConfigTools;
use DIQA\FacetedSearch2\Model\Request\DocumentByIdQuery;
use DIQA\FacetedSearch2\Setup;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

class DocumentByIdEndpoint extends Handler
{

    public function execute()
    {
        ConfigTools::initializeServersideConfig();
        $solrClient = ConfigTools::getFacetedSearchClient();
        $jsonBody = $this->getRequest()->getBody();
        $documentQuery = DocumentByIdQuery::fromJson($jsonBody);
        $response = $solrClient->requestDocument($documentQuery->getId());
        $r = new Response(json_encode($response));
        $r->setHeader('Content-Type', 'application/json');
        return $r;
    }

}