<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\TemplateParser\TemplateNode;
use DIQA\ChemExtension\TemplateParser\TemplateParser;
use DIQA\ChemExtension\Utils\HtmlTableEditor;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use OOUI\HtmlSnippet;
use OOUI\IndexLayout;
use OOUI\PanelLayout;
use OOUI\TabPanelLayout;
use OOUI\Widget;
use OutputPage;
use Parser;
use ParserOptions;
use Philo\Blade\Blade;
use Title;

abstract class ExperimentRenderer
{

    private $blade;
    protected $context;

    /**
     * ExperimentRenderer constructor.
     * @param $context
     */
    protected function __construct($context)
    {
        $this->context = $context;
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ( $views, $cache );
    }

    /**
     * @param PanelLayout $form
     * @return PanelLayout | string
     * @throws Exception
     */
    public function render()
    {

        OutputPage::setupOOUI();

        $repo = ExperimentRepository::getInstance();
        $experiment = $repo->getExperimentType($this->context['form']);

        if ($experiment->hasOnlyOneTab() || WikiTools::isInVisualEditor()) {
            return $this->getTabContent($this->context['name'], 0);
        }

        $tabPanels = [];
        $i = 1;
        foreach ($experiment->getTabs() as $tab) {

            $tabPanels[] = new TabPanelLayout($tab, [
                'classes' => [],
                'label' => $tab,
                'content' => new Widget([
                    'content' => new HtmlSnippet($this->getTabContent($this->context['name'], $i))
                ]),
                'expanded' => false,
                'framed' => true,
            ]);
            $i++;
        }

        $indexLayout = new IndexLayout([
            'infusable' => true,
            'expanded' => false,
            'autoFocus' => false,
            'classes' => ['experimentlist'],
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

    /**
     * @throws Exception
     */
    private function getTabContent($experimentName, $tabIndex): string
    {

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

    /**
     * Preprocesses template content before rendering
     *
     * @param $text
     * @return mixed
     */
    protected abstract function preProcessTemplate($text);

    /**
     * Postprocesses HTML table after rendering
     *
     * @param $html
     * @param $tabIndex
     * @return mixed
     */
    protected abstract function postProcessTable($html, $tabIndex);

}
