<?php

namespace DIQA\ChemExtension\Endpoints;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Title;
use Wikimedia\ParamValidator\ParamValidator;

class GetTitle extends SimpleHandler
{

    public function run()
    {

        $params = $this->getValidatedParams();

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);

        $searchTerm = strtolower($params['searchTerm'] ?? '') ;
        $cond = [
            "CONVERT(page_title USING utf8) LIKE '%$searchTerm%'"

        ];
        if (isset($params['namespace'])) {
            $cond[] = "page_namespace = " . $params['namespace'];
        }
        $res = $dbr->select('page', ['page_title', 'page_namespace'], $cond, __METHOD__, ['LIMIT' => 100]);

        $withNsPrefix = ($params['withNsPrefix'] ?? 'false') === 'true';
        $results = [];
        foreach ($res as $row) {
            $title = Title::newFromText($row->page_title, $row->page_namespace);
            $results[] = ['title' => $withNsPrefix ? $title->getPrefixedText() : $title->getText()];

        }
        return ['results' => $results];
    }

    public function needsWriteAccess()
    {
        return false;
    }

    public function getParamSettings()
    {
        return [
            'namespace' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => false,
            ],
            'withNsPrefix' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => false,
            ],
            'searchTerm' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => false,
            ],
        ];
    }
}