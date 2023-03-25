<?php

namespace DIQA\ChemExtension\NavigationBar;

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
}