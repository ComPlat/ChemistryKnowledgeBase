<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\Literature\DOITools;
use DIQA\ChemExtension\Utils\ChemTools;
use DIQA\ChemExtension\Utils\HtmlTableEditor;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonInputWidget;
use ParserOptions;
use RequestContext;
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
    protected function getContent(): string
    {
        $hooksContainer = MediaWikiServices::getInstance()->getHookContainer();
        $experimentType = ExperimentRepository::getInstance()->getExperimentType($this->context['form']);
        $mainTemplate = $experimentType->getMainTemplate();
        $rowTemplate = $experimentType->getRowTemplate();
        $templateData = $this->context['templateData'];
        $experiments = '';
        foreach ($templateData as $rows) {
            $templateParams = '';
            foreach ($rows as $key => $value) {
                $templateParams .= "\n|$key=$value";
                $chemFormId = ChemTools::getChemFormIdFromPageTitle($value);
                if (!is_null($chemFormId)) {
                    $hooksContainer->run('CollectMolecules', [$chemFormId, $this->context['page']]);
                }
            }
            $experiments .= "{{" . $rowTemplate . $templateParams . "\n}}";
        }
        $templateCall = <<<TEMPLATE
{{{$mainTemplate}
|experiments={$experiments}
}}
TEMPLATE;

        $parser = clone MediaWikiServices::getInstance()->getParser();
        $parserOutput = $parser->parse($templateCall, $this->context['page'], new ParserOptions(RequestContext::getMain()->getUser()));
        $html = $parserOutput->getText(['enableSectionEditLinks' => false]);

        $results = [];

        $htmlTableEditor = new HtmlTableEditor($html, null);
        $htmlTableEditor->removeEmptyColumns();

        $htmlTableEditor->addIndexAsFirstColumn();
        if (!WikiTools::isInVisualEditor()) {

            $links = [];
            $templateData = $this->context['templateData'];
            foreach ($templateData as $rows) {
                $basePageTitle = Title::newFromText($rows['BasePageName']);
                $doidata = null;
                if (!is_null($basePageTitle)) {
                    $hooksContainer->run('CollectPublications', [$basePageTitle, & $doidata]);
                    if (!is_null($doidata)) {
                        $reference = DOITools::generateReferenceIndex($doidata['data']);
                        $url = "#literature_$reference";
                        $fullUrl = $basePageTitle->getFullURL();
                        $withLiteratureRef = true;
                    } else {
                        $url = $basePageTitle->getFullURL();
                        $fullUrl = $url;
                        $reference = DOITools::generateReferenceIndexFromTitle($basePageTitle);
                        $withLiteratureRef = false;
                    }
                    $links[] = [
                        'url' => $url,
                        'fullUrl' => $fullUrl,
                        'tooltip' => $basePageTitle->getText(),
                        'label' => "[" . $reference . "]",
                        'withLiteratureRef' => $withLiteratureRef
                    ];
                } else {
                    $links[] = ['url' => $this->context['page']->getFullURL(), 'label' => "- no publication page found -"];
                }
            }
            $htmlTableEditor->addLinkAsLastColumn($links);
            //$htmlTableEditor->addPubLinkAsLastColumn($links);
            $htmlTableEditor->hideTables();
            $htmlTableEditor->addTableClass("experiment-link");
        } else {
            // required because VE can handle only limited amount of HTML
            $htmlTableEditor->shortenTable(25);
        }

        $uniqueId = uniqid();
        $toggleButton = new ButtonInputWidget([
            'classes' => ['chemext-button', 'experiment-link-show-button'],
            'id' => 'ce-show-investigation-' . $uniqueId,
            'type' => 'button',
            'label' => 'Show table',
            'flags' => ['primary', 'progressive'],
            'title' => 'Show summary table of investigations',
            'infusable' => true
        ]);

        $refreshButton = new ButtonInputWidget([
            'classes' => ['chemext-button', 'experiment-link-refresh-button'],
            'id' => 'ce-refresh-investigation-' . $uniqueId,
            'type' => 'button',
            'label' => 'Refresh',
            'flags' => ['primary', 'progressive'],
            'title' => 'Refresh content investigations',
            'infusable' => true,
            'value' => json_encode([
                'parameters' => $this->context['parameters'],
                'selectExperimentQuery' => $this->context['selectExperimentQuery'],
                'page' => $this->context['page']->getPrefixedText(),
                'cacheKey' => $this->context['cacheKey']
            ])
        ]);

        $exportButton = new ButtonInputWidget([
            'classes' => ['chemext-button', 'experiment-link-export-button'],
            'id' => 'ce-export-investigation-' . $uniqueId,
            'type' => 'button',
            'label' => 'Export',
            'flags' => ['primary', 'progressive'],
            'title' => 'Export investigation as excel file',
            'infusable' => true,
            'value' => json_encode([
                'parameters' => $this->context['parameters'],
                'selectExperimentQuery' => urldecode($this->context['selectExperimentQuery']),
                'page' => $this->context['page']->getPrefixedText(),
                'cacheKey' => $this->context['cacheKey'],
                'type' => 'link'
            ])
        ]);

        global $wgScriptPath;
        return $this->blade->run("experiment-link-table", [
            'htmlTableEditor' => $htmlTableEditor,
            'button' => WikiTools::isInVisualEditor() ? '' : $toggleButton->toString(),
            'refreshButton' => WikiTools::isInVisualEditor() ? '' : $refreshButton->toString(),
            'exportButton' => WikiTools::isInVisualEditor() ? '' : $exportButton->toString(),
            'description' => $this->context['description'],
            'buttonCounter' => $uniqueId,
            'cacheKey' => $this->context['cacheKey'],
            'wgScriptPath' => $wgScriptPath
        ]);
    }
}
