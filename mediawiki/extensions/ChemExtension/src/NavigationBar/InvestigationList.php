<?php

namespace DIQA\ChemExtension\NavigationBar;

use OOUI\FieldLayout;
use OOUI\TextInputWidget;
use eftec\bladeone\BladeOne;
use Title;

class InvestigationList {
    private $blade;
    private $title;
    /**
     * Breadcrumb constructor.
     */
    public function __construct(Title $title)
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new BladeOne ($views, $cache);
        $this->title = $title;
    }

    public function renderInvestigationList(): array
    {
        $investigationFinder = new InvestigationFinder();
        if ($this->title->getNamespace() === NS_CATEGORY) {
            $list = $investigationFinder->getInvestigationsForTopic($this->title);
        } else {
            $list = $investigationFinder->getInvestigationsForPublication($this->title);
        }
        return $list;

    }
    /**
     * @return FieldLayout
     * @throws \OOUI\Exception
     */
    public function createGUIForInvestigationFilter(): FieldLayout
    {
        return new FieldLayout(
            new TextInputWidget([
                'id' => 'ce-investigation-filter-input',
                'infusable' => true,
                'name' => 'investigation-filter',
                'value' => '',
                'placeholder' => 'Filter for investigations...'
            ]),
            [
                'align' => 'top',
                'label' => 'Filter'
            ]
        );
    }

}