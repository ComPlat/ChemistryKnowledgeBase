<?php

namespace DIQA\ChemExtension\Experiments;

class Experiment2 extends Experiment {

    public function getTemplate()
    {
        return "DemoPublication";
    }

    public function getTabs()
    {
        return [
            'tab1' => [
                'label' => 'Tab 1',
                'template' => 'DemoInvestigationEmbed',

            ]
        ];
    }

    public function getVEModeQuery()
    {
        return "[[Category:Experiment]]";
    }

}
