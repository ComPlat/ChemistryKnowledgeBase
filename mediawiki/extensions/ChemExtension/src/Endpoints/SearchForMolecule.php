<?php

namespace DIQA\ChemExtension\Endpoints;

use DIQA\ChemExtension\Utils\ChemTools;
use DIQA\ChemExtension\Utils\QueryUtils;
use MediaWiki\Rest\SimpleHandler;
use SMW\Query\QueryResult;
use Title;
use Wikimedia\ParamValidator\ParamValidator;

class SearchForMolecule extends SimpleHandler
{

    private $iupacNameProp;
    private $casProp;
    private $trivialnameProp;
    private $inchiKey;

    /**
     * SearchForMolecule constructor.
     */
    public function __construct()
    {
        $this->iupacNameProp = QueryUtils::newPropertyPrintRequest("IUPACName");
        $this->casProp = QueryUtils::newPropertyPrintRequest("CAS");
        $this->trivialnameProp = QueryUtils::newPropertyPrintRequest("Trivialname");
        $this->inchiKey = QueryUtils::newPropertyPrintRequest("InChIKey");
    }

    public function run()
    {
        $params = $this->getValidatedParams();
        $searchText = $params['searchText'];

        if (ChemTools::isChemformId($searchText)) {
            $searchResults = $this->searchForChemFormId($searchText);
        } else if (ChemTools::isCASNumber($searchText)) {
            $searchResults = $this->searchForCAS($searchText);
        } else {
            $searchResults = $this->generalSearch($searchText);
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

        ];
    }

    /**
     * @param $searchText
     * @param array $searchResults
     * @return array
     */
    private function generalSearch($searchText): array
    {

        $results = QueryUtils::executeBasicQuery(
            "[[Category:Molecule]][[IUPACName::~*$searchText*]] 
                        OR [[Category:Molecule]][[Synonym::~*$searchText*]]
                        OR [[Category:Molecule]][[Trivialname::~*$searchText*]]
                        OR [[Category:Molecule]][[Abbreviation::~*$searchText*]]", [
            $this->iupacNameProp, $this->casProp, $this->trivialnameProp, $this->inchiKey
        ]);
        return $this->readResults($results);
    }

    private function searchForCAS($casNumber)
    {
        $results = QueryUtils::executeBasicQuery(
            "[[Category:Molecule]][[CAS::$casNumber]]", [
            $this->iupacNameProp, $this->casProp, $this->trivialnameProp, $this->inchiKey
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
        $obj['label'] = $this->makeLabel($obj);
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

            $obj['label'] = $this->makeLabel($obj);
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
}