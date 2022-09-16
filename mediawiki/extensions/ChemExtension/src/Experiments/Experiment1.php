<?php

namespace DIQA\ChemExtension\Experiments;

class Experiment1 extends Experiment {

    public function getTemplate()
    {
        return "Experiment";
    }

    public function getTabs()
    {
        return [
            'tab1' => [
                'label' => 'Tab 1',
                'query' => '[[Category:Experiment]]',
                'printouts' => ['Field1']
                ],
            'tab2' => [
               'label' => 'Tab 2',
               'query' => '[[Category:Experiment]]',
               'printouts' => ['Field2']
           ]
        ];
    }

    public function getVEModeQuery()
    {
        return "[[Category:Experiment]]";
    }

}
