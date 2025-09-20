<?php

namespace DIQA\FacetedSearch2\Endpoints;

use DIQA\FacetedSearch2\ConfigTools;
use DIQA\FacetedSearch2\Model\Request\DocumentQuery;
use DIQA\FacetedSearch2\Setup;
use DIQA\FacetedSearch2\SolrClient\SolrRequestClient;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

class DocumentsSearchEndpoint extends Handler
{

    public function execute()
    {
        ConfigTools::initializeServersideConfig();
        $solrClient = ConfigTools::getFacetedSearchClient();
        $jsonBody = $this->getRequest()->getBody();
        $documentQuery = DocumentQuery::fromJson($jsonBody);
        $response = $solrClient->requestDocuments($documentQuery);
        $r = new Response(json_encode($response));
        $r->setHeader('Content-Type', 'application/json');
        return $r;
    }

}