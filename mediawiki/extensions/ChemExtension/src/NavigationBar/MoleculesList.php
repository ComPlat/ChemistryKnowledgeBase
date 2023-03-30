<?php


namespace DIQA\ChemExtension\NavigationBar;

use OOUI\FieldLayout;
use OOUI\TextInputWidget;

class MoleculesList
{

    public function getMolecules()
    {
        return $this->createGUIForMoleculeFilter();
    }

    private function createGUIForMoleculeFilter()
    {
        return new FieldLayout(
            new TextInputWidget([
                'id' => 'ce-molecules-filter',
                'infusable' => true,
                'name' => 'molecules-filter',
                'value' => '',
                'placeholder' => 'Filter for molecules...'
            ]),
            [
                'align' => 'top',
                'label' => 'Filter'
            ]
        );
    }
}