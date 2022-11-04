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
                'tabs' => null

            ],
            'DemoExperiment2' => [
                'label' => 'DemoInvestigation 2',
                'type' => 'molecular-process',
                'tabs' => ['tab1', 'tab2'],

            ],
            'Photocatalytic_CO2_conversion_experiments' => [
                'label' => 'Photocatalytic CO2 conversion',
                'type' => 'molecular-process',
                'tabs' => null,
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
     * @param $template
     * @return Experiment
     * @throws Exception
     */
    public function getExperimentType($template): ExperimentType
    {
        if (!array_key_exists($template, $this->experiments)) {
            return ExperimentType::fromForm($template);
        }
        return new ExperimentType($this->experiments[$template]);
    }

}