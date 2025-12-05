<?php

namespace DIQA\ChemExtension\PublicationImport;

use DIQA\ChemExtension\Experiments\ExperimentRepository;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\QueryUtils;

class ExperimentWikitextImporter {

    const PRE = '/<pre>(.*?)<\/pre>/s';
    private $text;

    /**
     * ExperimentWikitextImporter constructor.
     * @param $text
     */
    public function __construct($text)
    {
        $this->text = $text;
    }


    public function createInvestigationsFromCSV(): array
    {

        preg_match_all(self::PRE, $this->text, $matches);
        $index = 0;
        $investigationPages = [];

        foreach($matches[1] as $m) {
            $totalMatch = $matches[0][$index];
            $parseTableResult = $this->parseTable2WikiTemplateCalls($m);
            $allRows = $parseTableResult['allRows'];
            $headerFields = $parseTableResult['headerFields'];
            $experimentName = $this->detectInvestigationType($headerFields);
            $experimentType = ExperimentRepository::getInstance()->getExperimentType($experimentName);

            $mainTemplate = $experimentType->getMainTemplate();
            $rowTemplate = $experimentType->getRowTemplate();

            $invRowsContent = join("\n", array_map(fn($e) => "{{".$rowTemplate.$e."\n}}", $allRows));
            $invContent = "{{" . $mainTemplate . "|experiments=$invRowsContent\n}}";
            $invName = "inv$index";
            $investigationPages[$invName] = $invContent;
            $this->text = str_replace($totalMatch, "{{#experimentlist:|form=$mainTemplate|name=$invName}}", $this->text);
            $index++;
        }

        return [
            'investigationPages' => $investigationPages,
            'wikitext' => $this->text

        ];
    }

    private function parseTable2WikiTemplateCalls($m): array {
        $lines = explode("\n", trim($m));
        $header = array_shift($lines);
        $headerFields = array_map(fn($e) => trim(preg_replace('/\[[^]]*\]/', '', $e)), explode(",", $header));
        $allRows = [];
        foreach($lines as $l) {
            $columns = array_map(fn($e) => trim($e), explode(",", $l));
            while(count($headerFields) > count($columns)) {
                $columns[] = '';
            }
            $experiment = array_combine($headerFields, $columns);
            $rowContent = '';
            foreach ($experiment as $key => $value) {
                $possibleMolecule = $this->searchForMolecule($value);
                $value = $possibleMolecule['value'];
                $rowContent .= "\n|$key=$value";
            }
            $allRows[] = $rowContent;
        }
        return [ 'allRows' => $allRows, 'headerFields' => $headerFields ];
    }

    private function searchForMolecule($searchText): array
    {
        if (is_numeric($searchText)) {
            return [
                'value' => $searchText,
                'type' => 'plain'
            ];
        }
        $query = <<<QUERY
[[Category:Molecule]][[Synonym::~*$searchText*]]
OR [[Category:Molecule]][[Abbreviation::~*$searchText*]]
OR [[Category:Molecule]][[Trivialname::~*$searchText*]]
OR [[Category:Molecule]][[IUPACName::~*$searchText*]]
QUERY;
        $results = QueryUtils::executeBasicQuery($query);

        if ($results->getCount() === 0) {

            return [
                'value' => $searchText,
                'type' => 'plain'
            ];
        }
        $row = $results->getNext();
        $column = reset($row);
        $dataItem = $column->getNextDataItem();
        $chemFormId = $dataItem->getTitle()->getText();

        return [
            'value' => "Molecule:$chemFormId",
            'type' => 'chemformid'
        ];

    }

    private function detectInvestigationType(array $headerFields) {
        $allTypes = array_keys(ExperimentRepository::getInstance()->getAll());
        $numOfMatches = [];
        foreach($allTypes as $type) {
            $experimentType = ExperimentRepository::getInstance()->getExperimentType($type);
            $templateParamNames = array_values($experimentType->getProperties());
            $templateParamNames = ArrayTools::flatten($templateParamNames);
            $numOfMatches[$type] = count(array_intersect($templateParamNames, $headerFields));
        }
        $value = max($numOfMatches);
        return array_search($value, $numOfMatches);
    }
}
