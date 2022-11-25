<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\Utils\HtmlTableEditor;
use DIQA\ChemExtension\Utils\WikiTools;

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
}
