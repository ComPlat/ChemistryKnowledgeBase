<?php

namespace DIQA\ChemExtension\NavigationBar;

use DIQA\ChemExtension\Utils\WikiTools;
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
    private $pageList;

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
        $this->getReversedCategoryList($parentCategoryTree, $this->pageList);
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
        if (!WikiTools::checkIfInTopicCategory($this->title)) {
            $title = Title::newFromText("Topic", NS_CATEGORY);
        }
        $pubs = new PublicationList($title);
        $investigationList = new InvestigationList($this->title);
        $moleculesList = new MoleculesList($this->title);

        $html = $this->blade->view()->make("navigation.navigation-bar",
            [
                'title' => $this->title,
                'categories' => $this->makeBreadcrumb()->toString(),
                'categoryTree' => $treeHTML,
                'publicationList' => $pubs->getPublicationPages(),
                'investigationList' => $investigationList->renderInvestigationList(),
                'moleculesList' => $moleculesList->getMolecules(),
                'showPublications' => $this->showPublications(),
                'showInvestigations' => $this->showInvestigations(),
            ]
        )->render();

        return str_replace("\n", "", $html);
    }

    public function getPageTitle(): string
    {
        if (!WikiTools::checkIfInTopicCategory($this->title)) {
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
        if (!WikiTools::checkIfInTopicCategory($basePage)) {
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
        foreach($this->pageList as $page) {
            if ($page->getText() === 'Topic') continue;
            $li = new Tag('li');

            $typeHint = new Tag('span');
            $type = $this->getCssType($page);
            $li->appendContent($typeHint->addClasses(["ce-page-type-$type", 'ce-type-hint']));
            $a = new Tag('a');
            $a->appendContent($page->getText());
            $a->setAttributes(['href' => $page->getFullURL()]);
            $li->appendContent($a);
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

    private function showPublications(): bool
    {
        return !(WikiTools::checkIfInTopicCategory($this->title)
            && $this->title->getNamespace() == NS_MAIN);
    }

    private function showInvestigations(): bool
    {
        return (WikiTools::checkIfInTopicCategory($this->title)
            && !$this->title->isSubpage());
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
            case NS_MOLECULE:
            case NS_REACTION:
                $type = "molecules";
                break;
            default:
                $type = 'undefined';
        }
        return $type;
    }


}