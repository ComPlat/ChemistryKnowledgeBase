<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Experiments\ExperimentRepository;
use DIQA\ChemExtension\Utils\QueryUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use OOUI\FieldsetLayout;
use OOUI\HtmlSnippet;
use OOUI\IndexLayout;
use OOUI\PanelLayout;
use OOUI\TabPanelLayout;
use OOUI\Widget;
use OutputPage;
use Parser;
use ParserOptions;
use SMWQueryProcessor;
use Exception;
use Title;

class VEFormInput
{

    /**
     * @throws \OOUI\Exception
     * @throws \Exception
     */
    public static function renderVEFormInput(Parser $parser, $form)
    {
        $parametersAsStringArray = func_get_args();
        array_shift($parametersAsStringArray); // get rid of Parser
        $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

        if (WikiTools::isInVisualEditor()) {
            $html = self::renderInVisualEditor($parser, $parameters);
        } else {
            $html = self::renderInViewMode($parameters, $parser);
        }
        return [str_replace("\n", "", $html), 'noparse' => true, 'isHTML' => true];
    }

    private static function runQueryInternal($query, $printouts): string
    {
        $parameters = ['format' => 'table'];
        SMWQueryProcessor::addThisPrintout($printouts, $parameters);

        $processedParams = SMWQueryProcessor::getProcessedParams($parameters, $printouts);
        $smwQueryObject = SMWQueryProcessor::createQuery(
            $query,
            $processedParams,
            SMWQueryProcessor::SPECIAL_PAGE,
            '',
            $printouts
        );
        return SMWQueryProcessor::getResultFromQuery($smwQueryObject, $processedParams, SMW_OUTPUT_HTML,
            SMWQueryProcessor::INLINE_QUERY);
    }

    private static function getTabContent($data, $form, $baseTemplate = null): string
    {

        if (array_key_exists('template', $data)) {
            global $wgTitle;
            $subPage = $wgTitle->getText().'/'.$form;
            $text = WikiTools::getText(Title::newFromText($subPage));
            if (!is_null($baseTemplate)) {
                $text = str_replace($baseTemplate, $data['template'], $text);
            }
            $parser = new Parser();
            $parserOutput = $parser->parse($text, $wgTitle, new ParserOptions());
            return $parserOutput->getText();

        } else {
            $query = $data['query'];
            $printouts = $data['printouts'];
            $printouts = array_map(function ($p) {
                return QueryUtils::newPropertyPrintRequest($p);
            }, $printouts);
            return self::runQueryInternal($query, $printouts);
        }

    }

    /**
     * @return string
     */
    private static function renderInVisualEditor(Parser $parser, $parameters): string
    {
        $repo = ExperimentRepository::getInstance();
        $experiment = $repo->getExperiment($parameters['form']);

        if (array_key_exists('base-template', $experiment)) {

            $subPage = $parser->getTitle()->getText().'/'.$parameters['form'];
            $text = WikiTools::getText(Title::newFromText($subPage));

            $num = substr_count ($text, $experiment['base-template']);

            return "Experiment Typ: {$parameters['form']}<br/>Anzahl der Experimente: $num";
        } else {
            $queryResults = QueryUtils::executeBasicQueryCount($experiment['ve-mode-query'], [], []);
            $smwCount = $queryResults->getCountValue();
            return "Experiment Typ: {$parameters['form']}<br/>Anzahl der Experimente: $smwCount";
        }
    }

    /**
     * @param array $parameters
     * @param PanelLayout $form
     * @return PanelLayout or string
     * @throws Exception
     */
    private static function renderInViewMode(array $parameters)
    {

        OutputPage::setupOOUI();

        $repo = ExperimentRepository::getInstance();
        $experiment = $repo->getExperiment($parameters['form']);

        $baseTemplate = $experiment['base-template'] ?? null;
        if (count($experiment['tabs']) === 1) {
            $firstTab = reset($experiment['tabs']);
            return self::getTabContent($firstTab, $parameters['form'], $baseTemplate);
        }

        $tabPanels = [];
        foreach ($experiment['tabs'] as $tab => $data) {

            $tabPanels[] = new TabPanelLayout($tab, [
                'classes' => [],
                'label' => $data['label'],
                'content' => new FieldsetLayout([
                    'classes' => [],
                    'label' => $data['label'],
                    'items' => [
                        new Widget([
                            'content' => new HtmlSnippet(self::getTabContent($data, $parameters['form'], $baseTemplate))
                        ]),
                    ],
                ]),
                'expanded' => false,
                'framed' => true,
            ]);

        }

        $indexLayout = new IndexLayout([
            'infusable' => true,
            'expanded' => false,
            'autoFocus' => false,
            'classes' => ['veforminput'],
        ]);
        $indexLayout->addTabPanels($tabPanels);

        $form = new PanelLayout([
            'framed' => true,
            'expanded' => false,
            'classes' => [],
            'content' => $indexLayout
        ]);

        return $form;

    }

}
