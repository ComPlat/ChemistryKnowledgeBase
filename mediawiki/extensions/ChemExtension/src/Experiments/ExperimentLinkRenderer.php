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


    /**
     * @throws Exception
     */
    protected function getTabContent(): array
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

        $results = [];
        $htmlTableEditor = new HtmlTableEditor($html, null);
        $tabs = $htmlTableEditor->getTabs();

        foreach($tabs as $tab) {
            $htmlTableEditor = new HtmlTableEditor($html, null);
            $htmlTableEditor->removeEmptyColumns();
            if (!WikiTools::isInVisualEditor()) {
                $htmlTableEditor->removeOtherColumns($tab);

                $links = [];
                $templateData = $this->context['templateData'];
                foreach($templateData as $rows) {
                    $basePageTitle = Title::newFromText($rows['BasePageName']);
                    if (!is_null($basePageTitle)) {
                        $links[] = ['url' => $basePageTitle->getFullURL(), 'label' => $basePageTitle->getText()];
                    } else {
                        $links[] = ['url' => $this->context['page']->getFullURL(), 'label' => "- no publication page found -"];
                    }
                }
                $htmlTableEditor->addLinkAsLastColumn($links);
            }
            $results[$tab] = $this->blade->view ()->make ( "experiment-table", [
                'htmlTableEditor' => $htmlTableEditor,

            ])->render ();
        }

        return $results;
    }
}
