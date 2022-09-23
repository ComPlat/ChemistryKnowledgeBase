<?php

namespace DIQA\ChemExtension\Experiments;

use Exception;
use MediaWiki\Config\ConfigRepository;
use MediaWiki\MediaWikiServices;

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
            /*'experiment2' => [

                'tabs' => [
                    [
                        'label' => 'Tab 1',
                        'query' => '[[Category:Experiment]]',
                        'printouts' => ['Field1']
                    ], [
                        'label' => 'Tab 2',
                        'query' => '[[Category:Experiment]]',
                        'printouts' => ['Field2']
                    ]
                ],
                've-mode-query' => '[[Category:Experiment2]]'

            ]*/
        ];

    }

    public static function getInstance(): ExperimentRepository
    {
        if (is_null(self::$INSTANCE)) {
            self::$INSTANCE = new ExperimentRepository();
        }
        return self::$INSTANCE;
    }

    public function getAll() {
        return $this->experiments;
    }

    /**
     * @param $template
     * @return Experiment
     * @throws Exception
     */
    public function getExperimentType($template): array
    {
        if (!array_key_exists($template, $this->experiments)) {
            throw new Exception("Experiment does not exist: $template");
        }
        return $this->experiments[$template];
    }

    /**
     * @throws Exception
     */
    public function getFirstTab($template)
    {
        $experiment = $this->getExperimentType($template);
        return reset($experiment['tabs']);
    }

}