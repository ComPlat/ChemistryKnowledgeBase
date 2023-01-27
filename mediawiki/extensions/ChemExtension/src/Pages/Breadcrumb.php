<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\Utils\QueryUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Parser;
use ParserOptions;
use Philo\Blade\Blade;
use Title;

class Breadcrumb
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


    public function getNavigationLocation(Title $title)
    {
        if (is_null($title)) {
            return '';
        }
        $parentCategoryTree = [];
        $parentCategoryTree[$title->getPrefixedText()] = $title->getParentCategoryTree();

        $reversedCategories = [];
        $this->getReversedCategoryList($parentCategoryTree, $reversedCategories);

        $rootCategory = count($title->getParentCategories()) === 0 ? 'Topic' : $title->getText();
        $parser = new Parser();
        $parserOutput = $parser->parse('<categorytree mode="categories" depth="3" hideprefix="categories">' . $rootCategory . '</categorytree>'
            , $title, new ParserOptions());
        $html = $parserOutput->getText(['enableSectionEditLinks' => false]);

        $html = $this->blade->view()->make("breadcrumb",
            [
                'categories' => $this->makeTree($reversedCategories),
                'categoryTree' => $html,
            ]
        )->render();

        return WikiTools::sanitizeHTML($html);
    }

    private function makeTree(&$categoryList)
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

    public function getPageType(Title $title): string
    {
        if (is_null($title)) {
            return '';
        }
        $basePage = $title;
        if ($title->isSubpage()) {
            $basePage = Title::newFromText($title->getBaseText());
        }

        // check if base page has super category "Topic"
        $reversedCategories = [];
        $this->getReversedCategoryList($basePage->getParentCategoryTree(), $reversedCategories);
        $categoryTitles = array_map(function ($e) {
            return $e->getText();
        }, $reversedCategories);
        if (!in_array('Topic', $categoryTitles)) {
            return '';
        }

        switch ($title->getNamespace()) {
            case NS_CATEGORY:
                $type = 'topic';
                break;
            case NS_MAIN:
                $type = $title->isSubpage() ? 'investigation' : 'publication';
                break;
            default:
                $type = 'undefined';
        }
        return $this->blade->view()->make("page-type",
            [
                'type' => $type
            ]
        )->render();
    }

    private function getPublicationPages(Title $topic): string
    {
        $results = QueryUtils::executeBasicQuery("[[{$topic->getPrefixedText()}]]");
        $searchResults = [];
        while ($row = $results->getNext()) {
            $obj = [];
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            $obj['title'] = $dataItem->getTitle()->getPrefixedText();
            $searchResults[] = $obj;

        }
        return $this->blade->view()->make("publication-list",
            [
                'list' => $searchResults
            ]
        )->render();
    }
}