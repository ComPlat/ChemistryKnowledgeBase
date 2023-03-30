<?php


namespace DIQA\ChemExtension\NavigationBar;

use OOUI\FieldLayout;
use OOUI\TextInputWidget;
use Philo\Blade\Blade;

class MoleculesList
{
    private $blade;


    /**
     * Breadcrumb constructor.
     */
    public function __construct()
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ($views, $cache);

    }

    public function getMolecules()
    {
        $filter = $this->createGUIForMoleculeFilter();
        $moleculeList = $this->blade->view()->make("navigation.molecule-list",
            [
                'moleculesList' => []
            ]
        )->render();

        return $filter . $moleculeList;
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