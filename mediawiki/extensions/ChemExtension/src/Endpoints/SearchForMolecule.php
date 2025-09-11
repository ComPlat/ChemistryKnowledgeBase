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

class SearchForMolecule extends SimpleHandler
{

    const MAX_RESULTS = 500;

    private $iupacNameProp;
    private $casProp;
    private $trivialnameProp;
    private $inchiKey;
    private $abbreviation;

    private $repo;

    /**
     * SearchForMolecule constructor.
     */
    public function __construct()
    {
        $this->iupacNameProp = QueryUtils::newPropertyPrintRequest("IUPACName");
        $this->casProp = QueryUtils::newPropertyPrintRequest("CAS");
        $this->trivialnameProp = QueryUtils::newPropertyPrintRequest("Trivialname");
        $this->inchiKey = QueryUtils::newPropertyPrintRequest("InChIKey");
        $this->abbreviation = QueryUtils::newPropertyPrintRequest("Abbreviation");
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $this->repo = new ChemFormRepository($dbr);
    }

    public function run()
    {
        $params = $this->getValidatedParams();
        $searchText = $params['searchText'];
        $restrictTo = $params['restrictTo'] ?? null;
        $priorityProperties = $params['priorityProperties'] ?? [];

        $moleculeFilter = [];
        if (!is_null($restrictTo)) {
            $restrictToPage = Title::newFromText($restrictTo);
            $moleculeFilter = $this->getMoleculeFilter($restrictToPage);
        }

        if (ChemTools::isChemformId($searchText)) {
            $searchResults = $this->searchForChemFormId($searchText);
        } else if (ChemTools::isCASNumber($searchText)) {
            $searchResults = $this->searchForCAS($searchText);
        } else {
            $searchResults = $this->generalSearch($searchText, $moleculeFilter, $priorityProperties);
        }

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
            'restrictTo' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => false,
            ],
            'priorityProperties' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => false,
                ParamValidator::PARAM_ISMULTI => true
            ],

        ];
    }

    private function generalSearch($searchText, $moleculeFilter, $priorityProperties): array
    {
        $propertyQueryParts = array_map(function ($p) use ($searchText) {
            return "[[Category:Molecule]][[$p::~*$searchText*]]";
        }, $priorityProperties);

        $prioritizedResults = QueryUtils::executeBasicQuery(implode('', $propertyQueryParts),
            [
                $this->iupacNameProp, $this->casProp, $this->trivialnameProp, $this->inchiKey, $this->abbreviation
            ], ['limit' => 10000]);
        $allResults = $this->readResults($prioritizedResults);
        if (count($allResults) < self::MAX_RESULTS) {
            $generalResults = QueryUtils::executeBasicQuery(
                "[[Category:Molecule]][[IUPACName::~*$searchText*]] 
                            OR [[Category:Molecule]][[Synonym::~*$searchText*]]
                            OR [[Category:Molecule]][[Trivialname::~*$searchText*]]
                            OR [[Category:Molecule]][[Abbreviation::~*$searchText*]]", [
                $this->iupacNameProp, $this->casProp, $this->trivialnameProp, $this->inchiKey, $this->abbreviation
            ], ['limit' => 10000]);
            $allResults = array_merge($allResults, $this->readResults($generalResults));
        }

        if (count($moleculeFilter) > 0) {
            $allResults = array_filter($allResults, function ($e) use ($moleculeFilter) {
                return in_array($e['chemformid'], $moleculeFilter);
            });
        }
        return array_slice($allResults, 0, min(count($allResults), 500));
    }

    private function searchForCAS($casNumber)
    {
        $results = QueryUtils::executeBasicQuery(
            "[[Category:Molecule]][[CAS::$casNumber]]", [
            $this->iupacNameProp, $this->casProp, $this->trivialnameProp, $this->inchiKey, $this->abbreviation
        ]);
        return $this->readResults($results);
    }

    private function searchForChemFormId($chemFormId)
    {
        $moleculePage = Title::newFromText("$chemFormId", NS_MOLECULE);
        if (!$moleculePage->exists()) {
            return [];
        }
        $obj = [];
        $obj['title'] = $moleculePage->getPrefixedText();
        $obj['IUPACName'] = QueryUtils::getPropertyValuesAsString($moleculePage, 'IUPACName');
        $obj['CAS'] = QueryUtils::getPropertyValuesAsString($moleculePage, 'CAS');
        $obj['Trivialname'] = QueryUtils::getPropertyValuesAsString($moleculePage, 'Trivialname');
        $obj['InChIKey'] = QueryUtils::getPropertyValuesAsString($moleculePage, 'InChIKey');
        $obj['Abbreviation'] = QueryUtils::getPropertyValuesAsString($moleculePage, 'Abbreviation');
        $obj['displaytitle'] = $this->makeLabel($obj);
        $obj['label'] = $obj['displaytitle'];
        return [$obj];
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
            $obj = [];
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            $obj['title'] = $dataItem->getTitle()->getPrefixedText();
            $obj['chemformid'] = $dataItem->getTitle()->getText();

            $column = next($row);
            $dataItem = $column->getNextDataItem();
            $obj['IUPACName'] = $dataItem !== false ? $dataItem->getString() : '';

            $column = next($row);
            $dataItem = $column->getNextDataItem();
            $obj['CAS'] = $dataItem !== false ? $dataItem->getString() : '';

            $column = next($row);
            $dataItem = $column->getNextDataItem();
            $obj['Trivialname'] = $dataItem !== false ? $dataItem->getString() : '';

            $column = next($row);
            $dataItem = $column->getNextDataItem();
            $obj['InChIKey'] = $dataItem !== false ? $dataItem->getString() : '';

            $column = next($row);
            $dataItem = $column->getNextDataItem();
            $obj['Abbreviation'] = $dataItem !== false ? $dataItem->getString() : '';

            $obj['displaytitle'] = $this->makeLabel($obj);
            $obj['label'] = $obj['displaytitle'];
            $searchResults[] = $obj;

        }
        return $searchResults;
    }

    private function makeLabel($obj)
    {
        $labelToShow = $obj['Trivialname'] == '' ? $obj['title'] : $obj['Trivialname'];
        if ($obj['CAS'] != '') $labelToShow .= ", CAS: " . $obj['CAS'];
        return $labelToShow;
    }


    private function getMoleculeFilter(Title $restrictToPage): array
    {
        $moleculeIds = [];
        if ($restrictToPage->getNamespace() === NS_CATEGORY) {
            $moleculeIds = $this->repo->getMoleculeIdsUsedOnCategory($restrictToPage);
        } elseif ($restrictToPage->getNamespace() === NS_MAIN) {
            $moleculeIds = $this->repo->getChemFormIdsByPages([$restrictToPage]);
        }
        return $moleculeIds;
    }
}