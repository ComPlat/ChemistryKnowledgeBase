<?php

namespace DIQA\ChemExtension\NavigationBar;

use DIQA\ChemExtension\Utils\QueryUtils;
use OOUI\FieldLayout;
use OOUI\TextInputWidget;
use Philo\Blade\Blade;
use Title;

class PublicationList {

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

    public function getPublicationPages(): string
    {

        $results = QueryUtils::executeBasicQuery("[[{$this->title->getPrefixedText()}]]", [], ['limit' => 500]);
        $searchResults = [];
        while ($row = $results->getNext()) {
            $obj = [];
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            $obj['title'] = $dataItem->getTitle();
            $searchResults[] = $obj;

        }

        $filter = $this->createGUIForPublicationFilter();
        $publicationList = $this->blade->view()->make("navigation.publication-list",
            [
                'list' => $searchResults,
            ]
        )->render();

        return $filter . $publicationList;
    }

    /**
     * @return FieldLayout
     * @throws \OOUI\Exception
     */
    private function createGUIForPublicationFilter(): FieldLayout
    {
        return new FieldLayout(
            new TextInputWidget([
                'id' => 'ce-publication-filter',
                'infusable' => true,
                'name' => 'publication-filter',
                'value' => '',
                'placeholder' => 'Filter for publications...'
            ]),
            [
                'align' => 'top',
                'label' => 'Filter'
            ]
        );
    }

}