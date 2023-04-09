<?php

namespace DIQA\ChemExtension\NavigationBar;

use DIQA\ChemExtension\Utils\WikiTools;
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

    }

    public function getNavigationLocation()
    {
        global $wgScriptPath;
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
        $moleculesList = new MoleculesList(self::getCssType($this->title));

        $html = $this->blade->view()->make("navigation.navigation-bar",
            [
                'title' => $this->title,
                'type' => self::getCssType($this->title),
                'categories' => $this->makeBreadcrumb()->toString(),
                'categoryTree' => $treeHTML,
                'publicationList' => $pubs->getPublicationPages(),
                'publicationFilter' => $pubs->createGUIForPublicationFilter(),
                'investigationList' => $investigationList->renderInvestigationList(),
                'moleculesList' => $moleculesList->getMolecules(),
                'moleculesFilter' => $moleculesList->createGUIForMoleculeFilter(),
                'showPublications' => $this->showPublications(),
                'showInvestigations' => $this->showInvestigations(),
                'imgPath' => "$wgScriptPath/extensions/ChemExtension/skins/images",
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
        if (!WikiTools::checkIfInTopicCategory($basePage) && !$basePage->getNamespace() == NS_MOLECULE) {
            return '';
        }

        global $wgScriptPath;
        $type = self::getCssType($this->title);
        return $this->blade->view()->make("page-type",
            [
                'wgScriptPath' => $wgScriptPath,
                'type' => $type,
                'text' => $type
            ]
        )->render();
    }

    private function makeBreadcrumb() {
        $tree = new BreadcrumbTree($this->title);
        $firstChild = $tree->getTree()->firstChild();
        if (!is_null($firstChild) && $firstChild->getTitle()->getText() === 'Topic') {
            return $firstChild->serialize();
        }
        return $tree->getTree()->serialize();
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
    public static function getCssType($title): string
    {
        switch ($title->getNamespace()) {
            case NS_CATEGORY:
                $type = 'topic';
                break;
            case NS_MAIN:
                $type = $title->isSubpage() ? 'investigation' : 'publication';
                if ($type == 'publication' && !WikiTools::checkIfInTopicCategory($title)) {
                    $type = "undefined";
                }
                break;
            case NS_MOLECULE:
            case NS_REACTION:
                $type = "molecule";
                break;
            default:
                $type = "undefined";
        }
        return $type;
    }


}