<?php

namespace DIQA\ChemExtension\Endpoints;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use eftec\bladeone\BladeOne;
use Title;
use Wikimedia\ParamValidator\ParamValidator;

class GetPublications extends SimpleHandler
{
    private $blade;

    /**
     * GetPublications constructor.
     */
    public function __construct()
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new BladeOne ($views, $cache);
    }

    public function run()
    {

        $params = $this->getValidatedParams();

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);

        if (isset($params['category']) && $params['category'] != '') {
            $category = $dbr->addQuotes(str_replace(' ','_', $params['category']));
            $res = $dbr->select('page', 'page_id', "page_title = $category AND page_namespace = ".NS_CATEGORY);
            if ($res->numRows() > 0) {
                $row = $res->fetchObject();
                $category_id = $row->page_id;
            }
        }

        $searchText = strtolower($params['searchTerm']);
        $parts = explode(' ', $searchText);
        $parts = array_filter($parts, fn($e) => trim($e) !== '');

        $conditions = [
            'page.page_id = page_props.pp_page',
        ];
        foreach ($parts as $part) {
            $encoded = $dbr->addQuotes("%$part%");
            $conditions[] =   "LOWER(CONVERT(pp_value USING latin1)) LIKE $encoded";

        }
        $tables = ['page_props', 'page'];
        if (isset($category_id)) {
            $tables[] = 'category_index';
            $conditions[] = "page.page_id = category_index.page_id AND category_index.category_id = $category_id";
        }
        $res = $dbr->select($tables, ['DISTINCT page_title', 'page_namespace'], $conditions);
        $results = [];
        foreach ($res as $row) {
            $title = Title::newFromText($row->page_title, $row->page_namespace);
            $results[] = ['title' => $title ];

        }
        $html = $this->blade->run("navigation.publication-list",
            [
                'list' => $results,
            ]
        );
        return ['html' => $html];
    }

    public function needsWriteAccess()
    {
        return false;
    }

    public function getParamSettings()
    {
        return [
            'category' => [
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