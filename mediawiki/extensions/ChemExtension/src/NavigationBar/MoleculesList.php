<?php


namespace DIQA\ChemExtension\NavigationBar;

use OOUI\FieldLayout;
use OOUI\TextInputWidget;
use Philo\Blade\Blade;

class MoleculesList
{
    private $blade;
    private $type;

    /**
     * Breadcrumb constructor.
     */
    public function __construct($type)
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ($views, $cache);
        $this->type = $type;
    }

    public function getMolecules()
    {
        return $this->blade->view()->make("navigation.molecule-list",
            [
                'moleculesList' => []
            ]
        )->render();
    }

    public function createGUIForMoleculeFilter()
    {
        $placeholder = 'Filter for molecules ' . ($this->type != 'molecule' && $this->type != 'undefined' ? "of this $this->type" : "");
        return new FieldLayout(
            new TextInputWidget([
                'id' => 'ce-molecules-filter-input',
                'infusable' => true,
                'name' => 'molecules-filter',
                'value' => '',
                'placeholder' => $placeholder
            ]),
            [
                'align' => 'top',
                'label' => 'Filter'
            ]
        );
    }
}