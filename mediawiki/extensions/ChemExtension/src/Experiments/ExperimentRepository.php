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
                'label' => 'DemoExperiment 1',
                'type' => 'template',
                'base-row-template' => 'DemoExperiment1Row',
                'tabs' => [
                    [
                    'label' => 'Tab 1',
                    'header-template' => 'DemoExperiment1',
                    'row-template' => 'DemoExperiment1Row',
                    ]
                ],

            ],
            'DemoExperiment2' => [
                'label' => 'DemoExperiment 2',
                'type' => 'template',
                'base-row-template' => 'DemoExperiment2Row',
                'tabs' => [
                    [
                        'label' => 'Tab 1',
                        'header-template' => 'DemoExperiment2Tab1Header',
                        'row-template' => 'DemoExperiment2RowTab1',
                    ],
                    [
                        'label' => 'Tab 2',
                        'header-template' => 'DemoExperiment2Tab2Header',
                        'row-template' => 'DemoExperiment2RowTab2',
                    ]
                ],

            ],

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
            throw new Exception("Experiment does not exist: $template");
        }
        return new ExperimentType($template, $this->experiments[$template]);
    }

}