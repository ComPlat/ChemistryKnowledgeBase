<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\Utils\HtmlTableEditor;
use DIQA\ChemExtension\Utils\WikiTools;
use Parser;
use ParserOptions;
use Title;

class ExperimentLinkRenderer extends ExperimentRenderer
{

    public function __construct($context)
    {
        parent::__construct($context);
    }

    protected function postProcessTable($html, $tabIndex): HtmlTableEditor
    {
        $htmlTableEditor = new HtmlTableEditor($html, null);
        $htmlTableEditor->removeEmptyColumns();
        if (!WikiTools::isInVisualEditor()) {
            $htmlTableEditor->removeOtherColumns($tabIndex);

            $links = [];
            $templateData = $this->context['templateData'];
            foreach($templateData as $rows) {
                $basePageTitle = Title::newFromText($rows['BasePageName']);
                $links[] = ['url' => $basePageTitle->getFullURL(), 'label' => $basePageTitle->getText()];
            }
            $htmlTableEditor->addLinkAsLastColumn($links);
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
    protected function getTabContent($experimentName, $tabIndex): string
    {
        $experimentType = ExperimentRepository::getInstance()->getExperimentType($this->context['form']);
        $mainTemplate = $experimentType->getMainTemplate();
        $rowTemplate = $experimentType->getRowTemplate();
        $templateData = $this->context['templateData'];
        $experiments = '';
        foreach($templateData as $rows) {
            $templateParams = '';
            foreach($rows as $key => $value) {
                $templateParams .= "\n|$key=$value";
            }
            $experiments .= "{{".$rowTemplate . $templateParams . "\n}}";
        }
        $templateCall = <<<TEMPLATE
{{{$mainTemplate}
|experiments={$experiments}
}}
TEMPLATE;

        $parser = new Parser();
        $parserOutput = $parser->parse($templateCall, $this->context['page'], new ParserOptions());
        $html = $parserOutput->getText(['enableSectionEditLinks' => false]);

        $htmlTableEditor = $this->postProcessTable($html, $tabIndex);
        return $this->blade->view ()->make ( "experiment-table", [
            'htmlTableEditor' => $htmlTableEditor,

        ])->render ();
    }
}
