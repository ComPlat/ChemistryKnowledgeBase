<?php

namespace DIQA\ChemExtension\Experiments;

use SMW\Services\ServicesFactory as ApplicationFactory;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\ParserFunctions\ConvertQuantity;
use DIQA\ChemExtension\ParserFunctions\ExperimentLink;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\QueryUtils;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use SMW\DIProperty;
use SMW\DIWikiPage;

class ExperimentXlsExporter
{

    public const MOLFILE_SUFFIX = "_molfile";
    private $parameters;
    private $investigationPage;
    private $type;
    private $selectExperimentQuery;
    private $workSheet;
    private $chemFormRepo;
    private $moleculeCache;
    private $molfileProperty;

    public function __construct($exportDescriptor, Worksheet $workSheet)
    {
        $this->parameters = ArrayTools::propertiesToArray($exportDescriptor->parameters);
        $this->investigationPage = $exportDescriptor->investigationPage ?? '';
        $this->type = $exportDescriptor->type;
        $this->selectExperimentQuery = $exportDescriptor->selectExperimentQuery;
        $this->workSheet = $workSheet;
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $this->chemFormRepo = new ChemFormRepository($dbr);
        $this->moleculeCache = [];
        $this->molfileProperty = DIProperty::newFromUserLabel("Molfile");
    }

    public function export(): void
    {
        $experimentType = ExperimentRepository::getInstance()->getExperimentType($this->parameters['form']);
        $properties = $experimentType->getProperties();
        $exportProperties = $this->getExportProperties($properties);
        $this->exportHeaders($exportProperties);
        $this->exportRowData($exportProperties);
    }

    private function getDomainPropertyFromRecord($property): DIProperty
    {
        $spec = ApplicationFactory::getInstance()->getPropertySpecificationLookup()->getSpecification(
            DIProperty::newFromUserLabel($property), new DIProperty( '_LIST' ));
        $firstProperty = explode(";", $spec[0]->getString())[0];
        $firstProperty = trim($firstProperty);
        return DIProperty::newFromUserLabel($firstProperty);
    }

    private static function getContext(string $templateParam): string
    {
        if (!str_contains($templateParam, "__")) {
            try {
                $templateParam = Legacy::checkLegacyExperiments($templateParam);
                return explode("__", $templateParam)[1];
            } catch(\Exception $e) {
                return '-no context-';
            }
        }
        return trim(explode("__", $templateParam)[1] ?? '-no context-');
    }
    private function getMoleculeData($moleculeTitle)
    {
        if (is_null($moleculeTitle) || $moleculeTitle === '') {
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
        if (is_null($inchiKey)) {
            $inchiKey = $moleculeTitle;
        }
        $molfile = smwfGetStore()->getPropertyValues(DIWikiPage::newFromTitle($title), $this->molfileProperty);
        if (count($molfile) > 0) {
            $first = reset($molfile);
            $result = ['inchikey' => $inchiKey, 'molfile' => $first->getString()];
        } else {
            $result = ['inchikey' => $inchiKey, 'molfile' => ''];
        }
        $this->moleculeCache[$title->getPrefixedText()] = $result;
        return $result;
    }

    private function setBackgroundColor(int $column, $color): void
    {
        $this->workSheet->getStyle([$column, 1])
            ->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB($color);
    }

    private function storeHeaderWithUnit(string $property, string $templateParam, $column): void
    {
        $unit = ConvertQuantity::getDefaultUnit($property, $this->parameters['form']);
        $propertyTitle = Title::newFromText($property, SMW_NS_PROPERTY);
        $displayTitle = QueryUtils::getDisplayTitle($propertyTitle);
        $cellValue = is_null($unit) ? $displayTitle : "$displayTitle [$unit]";
        $cellValue .= " <$templateParam>";
        $this->workSheet->setCellValue([$column, 1], $cellValue);
    }

    private function storeHeaderWithInchiKey(string $property, string $templateParam, $column): void
    {
        $cellValue = $property . "_inchikey";
        $cellValue .= " <$templateParam>";
        $this->workSheet->setCellValue([$column, 1], $cellValue);
    }

    private function storeMolecule($moleculeTitle, int $column, int $rowIndex): void
    {
        $molecule = $this->getMoleculeData($moleculeTitle);
        if (is_null($molecule)) {
            $this->workSheet->setCellValue([$column, $rowIndex], $moleculeTitle);
            $this->workSheet->setCellValue([$column + 1, $rowIndex], '');
        } else {
            $this->workSheet->setCellValueExplicit([$column, $rowIndex], $molecule['inchikey'], DataType::TYPE_STRING);
            $this->workSheet->setCellValueExplicit([$column + 1, $rowIndex], '"' . $molecule['molfile'] . '"', DataType::TYPE_STRING);
        }

    }

    private function getExportProperties($properties): array
    {
        $exportProperties = [];
        foreach ($properties as $p => $templateParams) {
            $printRequest = QueryUtils::newPropertyPrintRequest($p);
            foreach ($templateParams as $templateParam) {
                $exportProperties[] = [
                    'property' => $p,
                    'type' => $printRequest->getTypeID(),
                    'templateParam' => $templateParam,
                ];
            }
        }
        return $exportProperties;
    }

    private function exportHeaders(array $exportProperties): void
    {
        $column = 1;

        foreach ($exportProperties as $exportProperty) {
            $type = $exportProperty['type'];
            $property = $exportProperty['property'];
            $templateParam = $exportProperty['templateParam'];

            if ($type === '_wpg' && $property !== 'BasePageName') {
                $this->storeHeaderWithInchiKey($property, $templateParam, $column);
            } else if ($type === '_rec') {
                $context = self::getContext($templateParam);
                $recProperty = $this->getDomainPropertyFromRecord($property);
                $propertyValueType = $recProperty->findPropertyValueType();
                $domainPropertyLabel = $recProperty->getLabel() . "($context)";
                if ($propertyValueType === '_wpg') {
                    $this->storeHeaderWithInchiKey($domainPropertyLabel, $templateParam, $column);
                } else {
                    $this->storeHeaderWithUnit($domainPropertyLabel, $templateParam, $column);
                }

            } else {
                $this->storeHeaderWithUnit($property, $templateParam, $column);
            }
            $this->setBackgroundColor($column, 'ffff00');

            // add molfile column (if necessary)
            if ($type === '_wpg' && $property !== 'BasePageName') {
                $column++;
                $this->workSheet->setCellValue([$column, 1], $property . self::MOLFILE_SUFFIX);
                $this->setBackgroundColor($column, 'ffff00');
            } else if ($type === '_rec') {
                $context = self::getContext($templateParam);
                $recProperty = $this->getDomainPropertyFromRecord($property);
                $propertyValueType = $recProperty->findPropertyValueType();
                if ($propertyValueType === '_wpg') {
                    $column++;
                    $this->workSheet->setCellValue([$column, 1], $property . " ($context)" . self::MOLFILE_SUFFIX);
                    $this->setBackgroundColor($column, 'ffff00');
                }
            }
            $column++;
        }

    }


    private function exportRowData(array $exportProperties): void
    {
        $rowIndex = 2;
        if ($this->type == 'list') {
            $this->selectExperimentQuery = '[[-Has subobject::' . $this->investigationPage . ']]';
        }
        $templateData = ExperimentLink::getTemplateData($this->parameters,
            $this->selectExperimentQuery,
            $this->parameters['onlyIncluded'] ?? true
        );
        foreach ($templateData as $row) {
            $column = 1;
            foreach ($exportProperties as $p) {
                $type = $p['type'];
                $property = $p['property'];
                $templateParam = $p['templateParam'];
                if ($type === '_wpg' && $property !== 'BasePageName') {
                    $this->storeMolecule($row[$templateParam], $column, $rowIndex);
                    $column += 2;
                } else if ($type === '_rec') {
                    $recProperty = $this->getDomainPropertyFromRecord($property);
                    $propertyValueType = $recProperty->findPropertyValueType();
                    if ($propertyValueType === '_wpg') {
                        $this->storeMolecule($row[$templateParam], $column, $rowIndex);
                        $column += 2;
                    } else {
                        $this->workSheet->setCellValue([$column, $rowIndex], $row[$templateParam]);
                        $column++;
                    }
                } else if ($type === '_boo') {
                    $this->workSheet->setCellValue([$column, $rowIndex], $row[$templateParam] ? 'true' : 'false');
                    $column++;
                } else {
                    $this->workSheet->setCellValue([$column, $rowIndex], $row[$templateParam]);
                    $column++;
                }
            }
            $rowIndex++;
        }
    }

}
