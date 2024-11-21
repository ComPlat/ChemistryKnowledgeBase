<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\Utils\QueryUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use MediaWiki\MediaWikiServices;
use OOUI\Tag;
use Philo\Blade\Blade;
use RequestContext;
use SpecialPage;
use Title;

class PageCreationSpecial extends SpecialPage
{

    private $blade;

    protected function __construct($title)
    {
        parent::__construct($title);
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ( $views, $cache );
    }

    protected function getHelpSection($title): Tag
    {
        $helpButton = new Tag('a');
        $helpPage = Title::newFromText($title);
        $helpButton->setAttributes(['href' => $helpPage->getFullURL(), 'target' => '_blank']);
        $helpButton->appendContent("Show help");
        $helpSection = new Tag('div');
        $helpSection->appendContent($helpButton);
        $helpSection->addClasses(['chemext-help-link']);
        return $helpSection;
    }

    protected function createPageAndRedirect(Title $pageTitle, string $superTopics, $doiData = null)
    {
            if ($pageTitle->exists()) {
                throw new Exception("Page creation failed because page already exists");
            }
            $existingPageTitle = $this->doiExists($doiData->DOI ?? '');
            if(!is_null($existingPageTitle)) {
                throw new Exception("The specified DOI is already used on a publication: [[{$existingPageTitle}]]");
            }

            global $wgScriptPath;
            $superTopicsAsWikiText = '';
            if (trim($superTopics) !== '') {
                $superTopicsAsWikiText = array_map(function ($topic) {
                    return "[[Category:$topic]]";
                }, explode("\n", $superTopics));
            }

            $pageContent = "";

            $doi = $doiData->DOI ?? '';
            $pageContent .= "{{DOI|doi=$doi}}\n";

            $pageContent .= implode("\n", $superTopicsAsWikiText);
            $pageContent .= "[[Category:Publication]]";

            $pageContent .="\n{{BaseTemplate}}";

            $successful = WikiTools::doEditContent($pageTitle, $pageContent, "auto-generated", EDIT_NEW);
            if ($successful) {
                header("Location: $wgScriptPath/index.php/{$pageTitle->getPrefixedDBKey()}?veaction=edit");
            } else {
                throw new Exception("Page creation failed. Try again");
            }

    }

    private function doiExists($doi) {
        $results = QueryUtils::executeBasicQuery("[[DOI::$doi]]");
        $exists = $results->getCount() > 0;
        $pageTitle = null;
        if ($exists) {
            $row = $results->getNext();
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            $pageTitle = $dataItem->getTitle()->getPrefixedText();
        }
        return $pageTitle;
    }

    protected function getPresetDataForTitleInput($paramValue) {
        if ($paramValue == '') {
            return [];
        }
        return explode("\n", $paramValue);
    }

    protected function showErrorHint($message) {
        $parser = clone MediaWikiServices::getInstance()->getParser();
        $parserOutput = $parser->parse($message, RequestContext::getMain()->getTitle(), new \ParserOptions(RequestContext::getMain()->getUser()));
        return $this->blade->view()->make("error", ['message' => $parserOutput->getText()])->render();
    }

}