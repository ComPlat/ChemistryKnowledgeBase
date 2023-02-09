<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\Utils\QueryUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use OOUI\FieldLayout;
use OOUI\TextInputWidget;
use Parser;
use ParserOptions;
use Philo\Blade\Blade;
use Title;
use OutputPage;

class Breadcrumb
{
    private $blade;

    private $title;
    private $storedCategories;

    /**
     * Breadcrumb constructor.
     */
    public function __construct(Title $title)
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ($views, $cache);

        $this->title = $title;
        $this->storedCategories = [];

        $parentCategoryTree = [];
        $parentCategoryTree[$this->title->getPrefixedText()] = $this->title->getParentCategoryTree();
        $this->storeAndReturnCategoryList($title, $parentCategoryTree);
    }

    public function getNavigationLocation()
    {
        if (is_null($this->title)) {
            return '';
        }
        OutputPage::setupOOUI();
        $rootCategories = $this->getRootCategoriesToDisplayAsTree();
        $parser = new Parser();
        $treeHTML = "";
        foreach ($rootCategories as $category) {
            $parserOutput = $parser->parse('<categorytree mode="categories" depth="3" hideprefix="categories" hideRoot="true">' . $category . '</categorytree>'
                , $this->title, new ParserOptions());
            $treeHTML .= $parserOutput->getText(['enableSectionEditLinks' => false]);
        }

        $html = $this->blade->view()->make("breadcrumb",
            [
                'title' => $this->title,
                'categories' => $this->makeTree($this->storedCategories[$this->title->getPrefixedText()]),
                'categoryTree' => $treeHTML,
                'publicationList' => $this->getPublicationPages(),
                'investigationList' => $this->getInvestigations(),
                'moleculesList' => $this->getMolecules(),
                'showPublications' => $this->showPublications(),
                'showInvestigations' => $this->showInvestigations(),
            ]
        )->render();

        return str_replace("\n", "", $html);
    }

    public function getPageTitle(): string
    {
        if (!$this->checkIfInTopicCategory($this->title)) {
            return $this->title->getPrefixedText();
        }
        return $this->title->getText();
    }

    public function getPageType(): string
    {
        if (is_null($this->title)) {
            return '';
        }
        $basePage = $this->title;
        if ($this->title->isSubpage()) {
            $basePage = Title::newFromText($this->title->getBaseText());
        }

        // check if base page has super category "Topic"
        if (!$this->checkIfInTopicCategory($basePage)) {
            return '';
        }

        switch ($this->title->getNamespace()) {
            case NS_CATEGORY:
                $type = 'topic';
                break;
            case NS_MAIN:
                $type = $this->title->isSubpage() ? 'investigation' : 'publication';
                break;
            default:
                $type = 'undefined';
        }
        return $this->blade->view()->make("page-type",
            [
                'type' => $type,
                'text' => $type
            ]
        )->render();
    }

    private function makeTree($categoryList): string
    {
        if (count($categoryList) > 0) {
            $category = array_shift($categoryList);
            if ($category->getText() === 'Topic') return $this->makeTree($categoryList);
            return "<ul><li><a href='{$category->getFullURL()}'>{$category->getText()}</a></li>" . $this->makeTree($categoryList) . "</ul>";
        }
        return '';
    }

    private function getReversedCategoryList($categories, &$allCategories)
    {
        foreach ($categories as $name => $super) {
            if (is_array($super) && count($super) > 0) {
                $this->getReversedCategoryList($super, $allCategories);
            }
            $allCategories[] = Title::newFromText($name);

        }
    }

    public function checkIfInTopicCategory(Title $title): bool
    {
        $categories = $this->storeAndReturnCategoryList($title, $title->getParentCategoryTree());
        $categoryTitles = array_map(function ($e) {
            return $e->getText();
        }, $categories);
        return in_array('Topic', $categoryTitles);
    }

    private function storeAndReturnCategoryList(Title $title, $parentCategoryTree)
    {
        if (!array_key_exists($title->getPrefixedText(), $this->storedCategories)) {
            $categories = [];
            $this->getReversedCategoryList($parentCategoryTree, $categories);
            $this->storedCategories[$title->getPrefixedText()] = $categories;
            return $categories;
        }
        return $this->storedCategories[$title->getPrefixedText()];
    }

    /**
     * @throws \OOUI\Exception
     */
    private function getPublicationPages(): string
    {
        $title = $this->title;
        if (!$this->checkIfInTopicCategory($this->title)) {
            $title = Title::newFromText("Topic", NS_CATEGORY);
        }
        $results = QueryUtils::executeBasicQuery("[[{$title->getPrefixedText()}]]", [], ['limit' => 500]);
        $searchResults = [];
        while ($row = $results->getNext()) {
            $obj = [];
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            $obj['title'] = $dataItem->getTitle();
            $searchResults[] = $obj;

        }

        $filter = $this->createGUIForPublicationFilter();
        $publicationList = $this->blade->view()->make("publication-list",
            [
                'list' => $searchResults,
            ]
        )->render();

        return $filter . $publicationList;
    }

    private function getInvestigations(): string
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

        //$filter = $this->createGUIForPublicationFilter();
        $experimentList = $this->blade->view()->make("experiment-list",
            [
                'list' => $results,
            ]
        )->render();

        return $experimentList;
    }

    private function showPublications()
    {
        return !($this->checkIfInTopicCategory($this->title)
            && $this->title->getNamespace() == NS_MAIN);
    }

    private function showInvestigations()
    {
        return ($this->checkIfInTopicCategory($this->title)
            && $this->title->getNamespace() == NS_MAIN && !$this->title->isSubpage());
    }

    /**
     * @return FieldLayout
     * @throws \OOUI\Exception
     */
    private function createGUIForPublicationFilter(): FieldLayout
    {
        $filter = new FieldLayout(
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
        return $filter;
    }

    /**
     * @return array|string[]
     */
    private function getRootCategoriesToDisplayAsTree(): array
    {
        $title = $this->title;
        if ($this->title->isSubpage()) {
            $title = $this->title->getBaseTitle();
        }
        if ($title->getNamespace() === NS_CATEGORY) {
            $rootCategories = count($title->getParentCategories()) === 0 ? ['Category:Topic'] : array_keys($title->getParentCategories());
            return array_map(function ($e) {
                return Title::newFromText($e)->getText();
            }, $rootCategories);
        } else {
            return ["Topic"];
        }
    }

    private function getMolecules()
    {
        $filter = $this->createGUIForMoleculeFilter();
        $moleculeList = $this->blade->view()->make("molecule-filter",
            [

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