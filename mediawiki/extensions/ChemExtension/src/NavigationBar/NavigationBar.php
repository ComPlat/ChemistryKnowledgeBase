<?php

namespace DIQA\ChemExtension\NavigationBar;

use OOUI\Tag;
use OutputPage;
use Parser;
use ParserOptions;
use Philo\Blade\Blade;
use Title;

class NavigationBar
{
    private $blade;

    private $title;
    private $categoryList;

    /**
     * Breadcrumb constructor.
     */
    public function __construct(Title $title)
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ($views, $cache);

        $this->title = $title;

        $parentCategoryTree = [];
        $parentCategoryTree[$this->title->getPrefixedText()] = $this->title->getParentCategoryTree();
        $this->getReversedCategoryList($parentCategoryTree, $this->categoryList);
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

        $title = $this->title;
        if (!$this->checkIfInTopicCategory($this->title)) {
            $title = Title::newFromText("Topic", NS_CATEGORY);
        }
        $pubs = new PublicationList($title);
        $investigations = new InvestigationList($this->title);
        $molecules = new MoleculesList($this->title);

        $html = $this->blade->view()->make("navigation.navigation-bar",
            [
                'title' => $this->title,
                'categories' => $this->makeBreadcrumb()->toString(),
                'categoryTree' => $treeHTML,
                'publicationList' => $pubs->getPublicationPages(),
                'investigationList' => $investigations->getInvestigations(),
                'moleculesList' => $molecules->getMolecules(),
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

        $type = $this->getCssType($this->title);
        return $this->blade->view()->make("page-type",
            [
                'type' => $type,
                'text' => $type
            ]
        )->render();
    }

    private function makeBreadcrumb() {

        $list = new Tag('ul');
        $list->addClasses(['ce-breadcrumb']);
        foreach($this->categoryList as $category) {
            if ($category->getText() === 'Topic') continue;
            $li = new Tag('li');

            $typeHint = new Tag('span');
            $type = $this->getCssType($category);
            $li->appendContent($typeHint->addClasses(["ce-page-type-$type", 'ce-type-hint']));
            $li->appendContent($category->getText());
            $list->appendContent($li);
        }
        return $list;
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
        $categoryTitles = array_map(function ($e) {
            return $e->getText();
        }, $this->categoryList);
        return in_array('Topic', $categoryTitles);
    }

    private function showPublications(): bool
    {
        return !($this->checkIfInTopicCategory($this->title)
            && $this->title->getNamespace() == NS_MAIN);
    }

    private function showInvestigations(): bool
    {
        return ($this->checkIfInTopicCategory($this->title)
            && $this->title->getNamespace() == NS_MAIN && !$this->title->isSubpage());
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

    /**
     * @return string
     */
    private function getCssType($title): string
    {
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
        return $type;
    }


}