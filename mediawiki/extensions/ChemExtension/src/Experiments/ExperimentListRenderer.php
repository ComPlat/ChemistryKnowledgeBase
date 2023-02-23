<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\ParserFunctions\ParserFunctionParser;
use DIQA\ChemExtension\Utils\ChemTools;
use DIQA\ChemExtension\Utils\HtmlTableEditor;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateParser;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateTextNode;
use DIQA\ChemExtension\Utils\WikiTools;
use Title;
use Exception;
use Parser;
use ParserOptions;
use Hooks;

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

        $parser = new Parser();
        $parserOutput = $parser->parse($text, $pageTitle, new ParserOptions());
        $html = $parserOutput->getText(['enableSectionEditLinks' => false, 'unwrap' => true]);
        $htmlTableEditor = new HtmlTableEditor($html, $this->context);
        $results = [];
        global $wgCEHiddenColumns;
        $tabs = $wgCEHiddenColumns === true ? [''] : $htmlTableEditor->getTabs();

        foreach($tabs as $tab) {
            $htmlTableEditor = new HtmlTableEditor($html, $this->context);
            $htmlTableEditor->removeEmptyColumns();
            if ($wgCEHiddenColumns ?? false) {
                $htmlTableEditor->collapseColumns();
            }
            if (!WikiTools::isInVisualEditor()) {
                if ($tab !== '') {
                    $htmlTableEditor->removeOtherColumns($tab);
                }
            } else {
                $htmlTableEditor->addEditButtonsAsFirstColumn();
            }
            $results[$tab] = $this->blade->view ()->make ( "experiment-table", [
                'htmlTableEditor' => $htmlTableEditor,
                'experimentName' => $experimentName
            ])->render ();
        }


        return $results;

    }
}
