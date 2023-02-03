<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Utils\QueryUtils;
use MediaWiki\Rest\SimpleHandler;
use Philo\Blade\Blade;
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
        $category = $params['category'];
        $searchTerm = trim(strtolower($params['searchTerm']));

        if ($searchTerm === '') {
            $query = "[[Category:$category]]";
        } else {
            $query = "[[Category:$category]][[Display title of::~*$searchTerm*]]";
        }

        $results = QueryUtils::executeBasicQuery(
            $query,
            [], ['limit' => 500]);

        $searchResults = [];
        while ($row = $results->getNext()) {
            $obj = [];
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            $obj['title'] = $dataItem->getTitle();
            $searchResults[] = $obj;

        }

        $publicationList = $this->blade->view()->make("publication-list",
            [
                'list' => $searchResults,
            ]
        )->render();

        return ['html' => $publicationList];
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
                ParamValidator::PARAM_REQUIRED => true,
            ],
        ];
    }
}