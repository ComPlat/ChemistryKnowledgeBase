<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Utils\ChemTools;
use DIQA\ChemExtension\Utils\QueryUtils;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use SMW\Query\QueryResult;
use Title;
use Wikimedia\ParamValidator\ParamValidator;

class SearchForTags extends SimpleHandler
{

    const MAX_RESULTS = 500;

    private $tagProperty;

    /**
     * SearchForMolecule constructor.
     */
    public function __construct()
    {
        $this->tagProperty = QueryUtils::newPropertyPrintRequest("Tag");

    }

    public function run()
    {
        $params = $this->getValidatedParams();
        $searchText = $params['searchText'];


        $searchResults = $this->generalSearch($searchText);


        return ['pfautocomplete' => $searchResults];
    }

    public function needsWriteAccess()
    {
        return false;
    }

    public function getParamSettings()
    {
        return [
            'searchText' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],

        ];
    }

    private function generalSearch($searchText): array
    {

        $prioritizedResults = QueryUtils::executeBasicQuery("[[Tag::~*$searchText*]]",
            [
                $this->tagProperty
            ], ['limit' => 10000]);
        $allResults = $this->readResults($prioritizedResults);

        return array_slice($allResults, 0, min(count($allResults), 500));
    }

    /**
     * @param \SMW\Query\QueryResult $results
     * @param array $searchResults
     * @return array
     */
    private function readResults(QueryResult $results): array
    {
        $searchResults = [];
        while ($row = $results->getNext()) {

            $column = reset($row);
            $column->getNextDataItem();

            $column = next($row);
            $dataItem = $column->getNextDataItem();
            while($dataItem !== false) {
                $searchResults[] = $dataItem->getString();
                $dataItem = $column->getNextDataItem();
            }



        }
        return $searchResults;
    }


}