<?php

namespace DIQA\FacetedSearch2\Endpoints;

use DIQA\FacetedSearch2\ConfigTools;
use DIQA\FacetedSearch2\Model\Request\StatsQuery;
use DIQA\FacetedSearch2\Setup;
use DIQA\FacetedSearch2\SolrClient\SolrRequestClient;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;


class StatsSearchEndpoint extends Handler
{

    public function execute()
    {
        ConfigTools::initializeServersideConfig();
        $solrClient = ConfigTools::getFacetedSearchClient();
        $jsonBody = $this->getRequest()->getBody();
        $query = StatsQuery::fromJson($jsonBody);
        $response = $solrClient->requestStats($query);
        $r = new Response(json_encode($response));
        $r->setHeader('Content-Type', 'application/json');
        return $r;
    }

}
