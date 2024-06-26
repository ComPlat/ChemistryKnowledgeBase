<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\Literature\DOITools;
use DIQA\ChemExtension\Utils\ChemTools;
use DIQA\ChemExtension\Utils\HtmlTableEditor;
use DIQA\ChemExtension\Utils\WikiTools;
use Hooks;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonInputWidget;
use Parser;
use ParserOptions;
use Title;

class ExperimentLinkRenderer extends ExperimentRenderer
{

    static $BUTTON_COUNTER = 0;

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
                $chemFormId = ChemTools::getChemFormIdFromPageTitle($value);
                if (!is_null($chemFormId)) {
                    Hooks::run('CollectMolecules', [$chemFormId, $this->context['page']]);
                }
            }
            $experiments .= "{{".$rowTemplate . $templateParams . "\n}}";
        }
        $templateCall = <<<TEMPLATE
{{{$mainTemplate}
|experiments={$experiments}
}}
TEMPLATE;
        $cache = MediaWikiServices::getInstance()->getMainObjectStash();
        $html = $cache->getWithSetCallback( $cache->makeKey( 'investigation-table', md5($templateCall)), $cache::TTL_DAY,
            function() use($templateCall){
                $parser = new Parser();
                $parserOutput = $parser->parse($templateCall, $this->context['page'], new ParserOptions());
                return $parserOutput->getText(['enableSectionEditLinks' => false]);
        });

        $results = [];
        $htmlTableEditor = new HtmlTableEditor($html, null);
        global $wgCEHiddenColumns;
        $tabs = $wgCEHiddenColumns === true ? [''] : $htmlTableEditor->getTabs();

        foreach($tabs as $tab) {
            $htmlTableEditor = new HtmlTableEditor($html, null);
            $htmlTableEditor->removeEmptyColumns();

            $htmlTableEditor->addIndexAsFirstColumn();
            if (!WikiTools::isInVisualEditor()) {
                if ($tab !== '') {
                    $htmlTableEditor->removeOtherColumns($tab);
                }

                $links = [];
                $templateData = $this->context['templateData'];
                foreach($templateData as $rows) {
                    $basePageTitle = Title::newFromText($rows['BasePageName']);
                    if (!is_null($basePageTitle)) {
                        $links[] = [
                            'url' => $basePageTitle->getFullURL(),
                            'tooltip' => $basePageTitle->getText(),
                            'label' => "[".DOITools::generateReferenceIndexFromTitle($basePageTitle)."]"
                        ];
                    } else {
                        $links[] = ['url' => $this->context['page']->getFullURL(), 'label' => "- no publication page found -"];
                    }
                }
                $htmlTableEditor->addLinkAsLastColumn($links);
                $htmlTableEditor->hideTables();
                $htmlTableEditor->addTableClass("experiment-link");
            } else {
                // required because VE can handle only limited amount of HTML
                $htmlTableEditor->shortenTable(25);
            }

            self::$BUTTON_COUNTER++;
            $pageTypes = new ButtonInputWidget([
                'classes' => ['chemext-button', 'experiment-link-show-button'],
                'id' => 'ce-show-investigation-'.self::$BUTTON_COUNTER,
                'type' => 'button',
                'label' => 'Show table',
                'flags' => ['primary', 'progressive'],
                'title' => 'Show summary table of investigations',
                'infusable' => true
            ]);

            $results[$tab] = $this->blade->view ()->make ( "experiment-link-table", [
                'htmlTableEditor' => $htmlTableEditor,
                'button' => WikiTools::isInVisualEditor() ? '' : $pageTypes->toString(),
                'description' => $this->context['description'],
                'buttonCounter' => self::$BUTTON_COUNTER

            ])->render ();
        }

        return $results;
    }
}
