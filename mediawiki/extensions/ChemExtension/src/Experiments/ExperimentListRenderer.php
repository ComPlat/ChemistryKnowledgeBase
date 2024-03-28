<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\ParserFunctions\ParserFunctionParser;
use DIQA\ChemExtension\Utils\ChemTools;
use DIQA\ChemExtension\Utils\HtmlTableEditor;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateParser;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateTextNode;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Hooks;
use MediaWiki\MediaWikiServices;
use ParserOptions;
use RequestContext;
use Title;

class ExperimentListRenderer extends ExperimentRenderer {

    public function __construct($context)
    {
        parent::__construct($context);
    }


    /**
     * @throws Exception
     */
    protected function getTabContent(): array
    {
        $experimentName = $this->context['name'];
        $pageTitle = $this->context['page'];
        $experimentPage = $pageTitle->getText() . '/' . $experimentName;
        $experimentPageTitle = Title::newFromText($experimentPage);
        if (!$experimentPageTitle->exists()) {
            throw new Exception("Experiment '$experimentPage' does not exist.");
        }

        $text = WikiTools::getText($experimentPageTitle);
        $templateParser = new TemplateParser($text);
        $ast = $templateParser->parse();
        $ast->visitNodes(function($node) {
            if (!($node instanceof TemplateTextNode)) return;
            $params = explode('|', $node->getText());
            $keyValues = ParserFunctionParser::parseArguments($params);
            foreach($keyValues as $key => $value) {
                $chemFormId = ChemTools::getChemFormIdFromPageTitle($value);
                if (!is_null($chemFormId)) {
                    Hooks::run('CollectMolecules', [$chemFormId, $this->context['page']]);
                }
            }
        });

        $cache = MediaWikiServices::getInstance()->getMainObjectStash();
        $html = $cache->getWithSetCallback( $cache->makeKey( 'investigation-table', md5($text)), $cache::TTL_DAY,
            function() use($text, $pageTitle){
                $parser = clone MediaWikiServices::getInstance()->getParser();
                $parserOutput = $parser->parse($text, $pageTitle, new ParserOptions(RequestContext::getMain()->getUser()));
                return $parserOutput->getText(['enableSectionEditLinks' => false]);
        });

        $htmlTableEditor = new HtmlTableEditor($html, $this->context);
        $results = [];
        global $wgCEHiddenColumns;
        $tabs = $wgCEHiddenColumns === true ? [''] : $htmlTableEditor->getTabs();

        foreach($tabs as $tab) {
            $htmlTableEditor = new HtmlTableEditor($html, $this->context);
            $htmlTableEditor->removeEmptyColumns();

            $htmlTableEditor->addIndexAsFirstColumn();
            if (!WikiTools::isInVisualEditor()) {
                if ($tab !== '') {
                    $htmlTableEditor->removeOtherColumns($tab);
                }
            } else {
                $htmlTableEditor->addEditButtonsAsFirstColumn();
                // required because VE can handle only limited amount of HTML
                $htmlTableEditor->shortenTable(25);
            }
            $htmlTableEditor->addTableClass("experiment-list");
            $results[$tab] = $this->blade->view ()->make ( "experiment-table", [
                'htmlTableEditor' => $htmlTableEditor,
                'experimentName' => $experimentName,
                'experimentPageTitle' => $experimentPageTitle,
                'inVisualEditor' => WikiTools::isInVisualEditor()
            ])->render ();
        }


        return $results;

    }
}
