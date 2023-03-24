<?php

namespace DIQA\ChemExtension\Experiments;

use Exception;

class ExperimentRepository
{

    private $experiments;
    private static $INSTANCE;

    public function __construct()
    {

        $this->experiments = [
            'DemoExperiment1' => [
                'label' => 'DemoInvestigation 1',
                'type' => 'assay',
                'rowTemplate' => 'DemoExperiment1Row'

            ],
            'DemoExperiment2' => [
                'label' => 'DemoInvestigation 2',
                'type' => 'molecular-process',
                'rowTemplate' => 'DemoExperiment2Row'
            ],
            'Photocatalytic_CO2_conversion_experiments' => [
                'label' => 'Photocatalytic CO2 conversion',
                'type' => 'molecular-process',
                'rowTemplate' => 'Photocatalytic_CO2_conversion'

            ],
            'Cyclic_Voltammetry_experiments' => [
                'label' => 'Cyclic Voltammetry experiments',
                'type' => 'assay',
                'rowTemplate' => 'Cyclic_Voltammetry'

            ]
        ];

    }

    public static function getInstance(): ExperimentRepository
    {
        if (is_null(self::$INSTANCE)) {
            self::$INSTANCE = new ExperimentRepository();
        }
        return self::$INSTANCE;
    }

    public function getAll(): array
    {
        return $this->experiments;
    }

    /**
     * @param $mainTemplate
     * @return Experiment
     * @throws Exception
     */
    public function getExperimentType($mainTemplate): ExperimentType
    {
        if ($mainTemplate === '') {
            throw new Exception("Experiment type is empty. Please specify.");
        }
        if (!array_key_exists($mainTemplate, $this->experiments)) {
            throw new Exception("Experiment type '{$mainTemplate}' does not exist.");
        }
        return new ExperimentType($this->experiments[$mainTemplate], $mainTemplate);
    }

}