<?php

namespace DIQA\ChemExtension\NavigationBar;

use DIQA\ChemExtension\Utils\QueryUtils;
use OOUI\FieldLayout;
use OOUI\TextInputWidget;
use eftec\bladeone\BladeOne;
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
        $this->blade = new BladeOne ($views, $cache);
        $this->title = $title;
    }

    public function getPublicationPages(): array
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
        return $searchResults;
    }

    /**
     * @return FieldLayout
     * @throws \OOUI\Exception
     */
    public function createGUIForPublicationFilter(): FieldLayout
    {
        return new FieldLayout(
            new TextInputWidget([
                'id' => 'ce-publication-filter-input',
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