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

    public function getInvestigations(): string
    {
        $results = [];
        $subPages = $this->title->getSubpages();
        if (is_array($subPages) && count($subPages) === 0) {
            return '';
        }

        while ($subPages->current()) {
            $results[] = $subPages->current();
            $subPages->next();
        }

        return $this->blade->view()->make("navigation.investigation-list",
            [
                'list' => $results,
            ]
        )->render();
    }
}