<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\Utils\HtmlTableEditor;
use DIQA\ChemExtension\Utils\WikiTools;
use Title;
use Exception;
use Parser;
use ParserOptions;

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

        $parser = new Parser();
        $parserOutput = $parser->parse($text, $pageTitle, new ParserOptions());
        $html = $parserOutput->getText(['enableSectionEditLinks' => false]);
        $htmlTableEditor = new HtmlTableEditor($html, $this->context['form']);

        $results = [];
        $tabs = $htmlTableEditor->getTabs();

        foreach($tabs as $tab) {
            $htmlTableEditor = new HtmlTableEditor($html, $this->context['form']);
            $htmlTableEditor->removeEmptyColumns();
            if (!WikiTools::isInVisualEditor()) {
                $htmlTableEditor->removeOtherColumns($tab);
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
