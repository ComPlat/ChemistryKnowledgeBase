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

    protected function postProcessTable($html, $tabIndex): HtmlTableEditor
    {
        $htmlTableEditor = new HtmlTableEditor($html, $this->context['form']);
        $htmlTableEditor->removeEmptyColumns();
        if (!WikiTools::isInVisualEditor()) {
            $htmlTableEditor->removeOtherColumns($tabIndex);
        } else {
            $htmlTableEditor->addEditButtonsAsFirstColumn();
        }

        return $htmlTableEditor;
    }

    protected function preProcessTemplate($text)
    {
        return $text;
    }

    /**
     * @throws Exception
     */
    protected function getTabContent($tabIndex): string
    {
        $experimentName = $this->context['name'];
        $pageTitle = $this->context['page'];
        $experimentPage = $pageTitle->getText() . '/' . $experimentName;
        $experimentPageTitle = Title::newFromText($experimentPage);
        if (!$experimentPageTitle->exists()) {
            throw new Exception("Experiment '$experimentPage' does not exist.");
        }

        $text = WikiTools::getText($experimentPageTitle);

        $text = $this->preProcessTemplate($text);

        $parser = new Parser();
        $parserOutput = $parser->parse($text, $pageTitle, new ParserOptions());
        $html = $parserOutput->getText(['enableSectionEditLinks' => false]);

        $htmlTableEditor = $this->postProcessTable($html, $tabIndex);

        return $this->blade->view ()->make ( "experiment-table", [
            'htmlTableEditor' => $htmlTableEditor,
            'experimentName' => $experimentName
        ])->render ();

    }
}
