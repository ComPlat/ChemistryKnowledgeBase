<?php

namespace DIQA\ChemExtension\NavigationBar;

use OOUI\FieldLayout;
use OOUI\TextInputWidget;
use Philo\Blade\Blade;
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
        $this->blade = new Blade ($views, $cache);
        $this->title = $title;
    }

    public function renderInvestigationList(): string
    {
        $investigationFinder = new InvestigationFinder();
        if ($this->title->getNamespace() === NS_CATEGORY) {
            $list = $investigationFinder->getInvestigationsForTopic($this->title);
            $type = "topic";
        } else {
            $list = $investigationFinder->getInvestigationsForPublication($this->title);
            $type = "publication";
        }
        return $this->blade->view()->make("navigation.investigation-list",
            [
                'list' => $list,
                'type' => $type,
            ]
        )->render();
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