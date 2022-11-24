<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\Utils\HtmlTableEditor;
use DIQA\ChemExtension\Utils\WikiTools;

class ExperimentLinkRenderer extends ExperimentRenderer
{

    public function __construct($context)
    {
        parent::__construct($context);
    }

    protected function postProcessTable($html, $tabIndex): HtmlTableEditor
    {
        $htmlTableEditor = new HtmlTableEditor($html, null);
        if (!WikiTools::isInVisualEditor()) {
            $htmlTableEditor->removeOtherColumns($tabIndex);
            $htmlTableEditor->removeEmptyColumns();
        }
        if (!is_null($this->context['index'])) {
            $htmlTableEditor->retainRows($this->context['index']);
        }

        return $htmlTableEditor;
    }

    protected function preProcessTemplate($text)
    {
        return $text;
    }
}
