<?php

namespace DIQA\ChemExtension\Endpoints;

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Philo\Blade\Blade;
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
        $this->blade = new Blade ($views, $cache);
    }

    public function run()
    {

        $params = $this->getValidatedParams();

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);

        if (isset($params['category'])) {
            $category = $dbr->addQuotes(str_replace(' ','_', $params['category']));
            $res = $dbr->select('page', 'page_id', "page_title = $category AND page_namespace = ".NS_CATEGORY);
            if ($res->numRows() > 0) {
                $row = $res->fetchObject();
                $category_id = $row->page_id;
            }
        }

        $searchtext = strtolower($params['searchTerm']);
        $searchtext = $dbr->addQuotes("%$searchtext%");

        $conds = [
            'page.page_id = page_props.pp_page',
            "LOWER(CONVERT(pp_value USING latin1)) LIKE $searchtext"
        ];
        $tables = ['page_props', 'page'];
        if (isset($category_id)) {
            $tables[] = 'category_index';
            $conds[] = "page.page_id = category_index.page_id AND category_index.category_id = $category_id";
        }
        $res = $dbr->select($tables, ['page_title', 'page_namespace'], $conds);
        $results = [];
        foreach ($res as $row) {
            $title = Title::newFromText($row->page_title, $row->page_namespace);
            $results[] = ['title' => $title ];

        }
        $html = $this->blade->view()->make("navigation.publication-list",
            [
                'list' => $results,
            ]
        )->render();
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
                ParamValidator::PARAM_REQUIRED => true,
            ],
            'searchTerm' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => false,
            ],
        ];
    }
}