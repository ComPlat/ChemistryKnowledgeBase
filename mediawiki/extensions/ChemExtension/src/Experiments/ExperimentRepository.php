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
                'rowTemplate' => 'DemoExperiment1Row',
                'tabs' => null

            ],
            'DemoExperiment2' => [
                'label' => 'DemoInvestigation 2',
                'type' => 'molecular-process',
                'rowTemplate' => 'DemoExperiment2Row',
                'tabs' => ['tab1', 'tab2'],

            ],
            'Photocatalytic_CO2_conversion_experiments' => [
                'label' => 'Photocatalytic CO2 conversion',
                'type' => 'molecular-process',
                'rowTemplate' => 'Photocatalytic_CO2_conversion',
                'tabs' => null,
                'properties' => [
                    'Catalyst' => 'catalyst',
                    'Catalyst concentration' => 'cat conc',
                    'Photosensitizer' => 'PS',
                    'Photosensitizer concentration' => 'PS conc',
                    'Electron donor' => 'e-D',
                    'Electron donor concentration' => 'e-D conc',
                    'Hydrogen donor' => 'H-D',
                    'Hydrogen donor concentration' => 'H-D conc',
                    'Solvent A' => 'solvent A',
                    'Solvent B' => 'solvent B',
                    'Solvent C' => 'solvent C',
                    'Solvent ratio' => 'solvent-ratio',
                    'Additives' => 'additives',
                    'Additives concentration' => 'additives conc',
                    'PH value' => 'pH',
                    'Temperature' => 'Temperature',
                    'Excitation wavelength' => 'λexc',
                    'Irradiation time' => 'irr time',
                    'Turnover number CO' => 'TON CO',
                    'Turnover number CH4' => 'TON CH4',
                    'Turnover number H2' => 'TON H2',
                    'Turnover number HCOOH' => 'TON HCOOH',
                    'Turnover number MeOH' => 'TON MeOH',
                    'Turnover frequency C0' => 'TOF CO',
                    'Turnover frequency CH4' => 'TOF CH4',
                    'Turnover frequency H2' => 'TOF H2',
                    'Turnover frequency HCOOH' => 'TOF HCOOH',
                    'Turnover frequency MeOH' => 'TOF MeOH',
                    'Quantum yield CH4' => 'Φ CH4',
                    'Quantum yield H2' => 'Φ H2',
                    'Quantum yield CO' => 'Φ CO',
                    'Quantum yield HCOOH' => 'Φ HCOOH',
                    'Quantum yield MeOH' => 'Φ MeOH',
                    'Quantum yield total' => 'Φ all',

                    'Included' => 'include',
                    "BasePageName" => 'BasePageName'
                ]
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