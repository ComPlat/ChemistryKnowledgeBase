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
            'DemoPublication' => [

                'base-template' => 'DemoInvestigationEmbed',
                'tabs' => [
                    [
                    'label' => 'Tab 1',
                    'template' => 'DemoInvestigationEmbed',
                    ]
                ],

            ],
            'experiment2' => [

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

    /**
     * @param $template
     * @return Experiment
     * @throws Exception
     */
    public function getExperiment($template): array
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
        $experiment = $this->getExperiment($template);
        return reset($experiment['tabs']);
    }

}