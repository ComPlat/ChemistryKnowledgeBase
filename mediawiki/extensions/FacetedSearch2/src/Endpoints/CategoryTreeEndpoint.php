<?php

namespace DIQA\FacetedSearch2\Endpoints;

use DIQA\FacetedSearch2\Model\Response\CategoryNode;
use DIQA\FacetedSearch2\Update\MWDBReader;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\Response;

class CategoryTreeEndpoint extends Handler
{

    public function execute()
    {
        $mwReader = new MWDBReader();
        $root = CategoryNode::fromTuples($mwReader->getCategoryTuples());
        $r = new Response(json_encode($root));
        $r->setHeader('Content-Type', 'application/json');
        return $r;
    }

}