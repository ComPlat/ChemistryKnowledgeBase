<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\ParserFunctions\ExperimentLink;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\QueryUtils;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExperimentXlsExporter
{

    private $parameters;
    private $selectExperimentQuery;
    private $workSheet;
    private $chemFormRepo;
    private $moleculeCache;
    private $molfileProperty;

    public function __construct($exportDescriptor, Worksheet $workSheet)
    {
        $this->parameters = ArrayTools::propertiesToArray($exportDescriptor->parameters);
        $this->selectExperimentQuery = $exportDescriptor->selectExperimentQuery;
        $this->workSheet = $workSheet;
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $this->chemFormRepo = new ChemFormRepository($dbr);
        $this->moleculeCache = [];
        $this->molfileProperty = \SMWDIProperty::newFromUserLabel("Molfile");
    }

    public function export()
    {

        $experimentType = ExperimentRepository::getInstance()->getExperimentType($this->parameters['form']);
        $properties = $experimentType->getProperties();
        $column = 1;
        $exportProperties = [];
        foreach ($properties as $p => $templateParam) {
            $printRequest = QueryUtils::newPropertyPrintRequest($p);
            $exportProperties[] = [
                'property' => $p,
                'type' => $printRequest->getTypeID(),
                'templateParam' => $templateParam,

            ];
            $this->workSheet->setCellValue([$column, 1], $p);
            $column++;
        }

        $rowIndex = 2;
        $templateData = ExperimentLink::getTemplateData($this->parameters, $this->selectExperimentQuery);
        foreach ($templateData as $row) {
            $column = 1;
            foreach($exportProperties as $p) {
                if ($p['type'] === '_wpg') {
                    $molecule = $this->exportMolecule($row[$p['templateParam']]);
                    if (is_null($molecule)) {
                        $this->workSheet->setCellValue([$column, $rowIndex], $row[$p['templateParam']]);
                    } else {
                        $cellContent = $molecule['inchikey'] . "\n" . $molecule['molfile'];
                        $this->workSheet->setCellValueExplicit([$column, $rowIndex], $cellContent, DataType::TYPE_STRING);
                    }
                } else if ($p['type'] === '_boo') {
                    $this->workSheet->setCellValue([$column, $rowIndex], $row[$p['templateParam']] ? 'true' : 'false');
                } else {
                    $this->workSheet->setCellValue([$column, $rowIndex], $row[$p['templateParam']]);
                }
                $column++;
            }
            $rowIndex++;
        }
    }

    private function exportMolecule($moleculeTitle) {
        if (is_null($moleculeTitle)) {
            return null;
        }
        if (array_key_exists($moleculeTitle, $this->moleculeCache)) {
            return $this->moleculeCache[$moleculeTitle];
        }

        $title = Title::newFromText($moleculeTitle);
        if ($title->getNamespace() !== NS_MOLECULE) {
            return null;
        }

        $inchiKey = $this->chemFormRepo->getMoleculeKey($title->getText());
        $molfile = smwfGetStore()->getPropertyValues(\SMWDIWikiPage::newFromTitle($title), $this->molfileProperty);
        if (count($molfile) > 0) {
            $first = reset($molfile);
            $result = ['inchikey' => $inchiKey, 'molfile' => $first->getString()];
        } else {
            $result = ['inchikey' => $inchiKey, 'molfile' => ''];
        }
        $this->moleculeCache[$title->getPrefixedText()] = $result;
        return $result;
    }
}
