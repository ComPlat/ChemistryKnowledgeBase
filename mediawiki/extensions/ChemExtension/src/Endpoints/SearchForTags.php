<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\TIB\TibClient;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\QueryUtils;
use MediaWiki\Rest\SimpleHandler;
use SMW\Query\QueryResult;
use Wikimedia\ParamValidator\ParamValidator;
use Exception;

class SearchForTags extends SimpleHandler
{

    const MAX_RESULTS = 50;

    private $tagProperty;
    private $logger;

    /**
     * SearchForMolecule constructor.
     */
    public function __construct()
    {
        $this->tagProperty = QueryUtils::newPropertyPrintRequest("Tag");
        $this->logger = new LoggerUtils('SearchForTags', 'ChemExtension');
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
            ], ['limit' => self::MAX_RESULTS]);
        $allResults = $this->readResults($prioritizedResults);
        $wikiResults = array_slice($allResults, 0, min(count($allResults), self::MAX_RESULTS));
        $wikiResults = array_map(fn($e) => ['label' => $e, 'ontology' => ''], $wikiResults);
        $tibResults = [];
        try {
            $tibClient = new TibClient();
            $tibResults = $tibClient->search($searchText, self::MAX_RESULTS - count($wikiResults));
        } catch(Exception $e) {
            $this->logger->warn($e->getMessage());
        }
        return array_merge($wikiResults, $tibResults);
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