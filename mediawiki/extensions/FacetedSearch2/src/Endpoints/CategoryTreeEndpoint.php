<?php

namespace DIQA\FacetedSearch2\Endpoints;

use DIQA\FacetedSearch2\CategoryNode;
use DIQA\FacetedSearch2\CategoryTreeGenerator;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

class CategoryTreeEndpoint extends Handler
{

    public function execute()
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $treeGenerator = new CategoryTreeGenerator($dbr);
        $root = CategoryNode::fromTuples($treeGenerator->getCategoryTuples());
        $r = new Response(json_encode($root));
        $r->setHeader('Content-Type', 'application/json');
        return $r;
    }

}