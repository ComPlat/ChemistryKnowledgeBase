<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\ParserFunctions\ParserFunctionParser;
use DIQA\ChemExtension\Utils\HtmlTableEditor;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateNode;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateParser;
use DIQA\ChemExtension\Utils\WikiTools;

class ExperimentLinkRenderer extends ExperimentRenderer
{

    public function __construct($context)
    {
        parent::__construct($context);
    }

    protected function postProcessTable($html, $tabIndex): HtmlTableEditor
    {
        $htmlTableEditor = new HtmlTableEditor($html, $this->context['form']);
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
        if (!is_null($this->context['index'])) {
            return $text;
        }
        $templateParser = new TemplateParser($text);
        $root = $templateParser->parse();
        $root->getFirstChild()->removeNodes(function ($node) {
            if (!($node instanceof TemplateNode)) {
                return false;
            }
            $parametersAsStringArray = explode('|', $node->getTextContent());
            $parametersAsStringArray = array_map(function ($e) { return trim($e); }, $parametersAsStringArray);
            $params = ParserFunctionParser::parseArguments($parametersAsStringArray);
            return ($params['include'] ?? '') != 'Yes';
        });
        return $root->serialize();
    }
}
