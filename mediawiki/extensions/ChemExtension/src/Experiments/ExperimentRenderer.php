<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\TemplateParser\TemplateNode;
use DIQA\ChemExtension\TemplateParser\TemplateParser;
use DIQA\ChemExtension\TemplateParser\TemplateTextNode;
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
use Title;

class ExperimentRenderer
{

    private $context;

    /**
     * ExperimentRenderer constructor.
     * @param $context
     */
    public function __construct($context)
    {
        $this->context = $context;
    }


    /**
     * @return string
     * @throws Exception
     */
    public function renderInVisualEditor(Parser $parser, $parameters): string
    {
        $repo = ExperimentRepository::getInstance();
        $experiment = $repo->getExperimentType($parameters['form']);

        $subPage = $parser->getTitle()->getText() . '/' . $parameters['form'];
        $text = WikiTools::getText(Title::newFromText($subPage));

        $num = substr_count($text, $experiment->getBaseRowTemplate());

        return "Experiment Typ: {$parameters['form']}<br/>Anzahl der Experimente: $num";

    }

    /**
     * @param array $parameters
     * @param PanelLayout $form
     * @return PanelLayout or string
     * @throws Exception
     */
    public function renderInViewMode(array $parameters)
    {

        OutputPage::setupOOUI();

        $repo = ExperimentRepository::getInstance();
        $experiment = $repo->getExperimentType($this->context['form']);

        if ($experiment->hasOnlyOneTab()) {
            return $this->getTabContent($experiment, 0);
        }

        $tabPanels = [];
        $i = 0;
        foreach ($experiment->getTabs() as $tab => $data) {

            $tabPanels[] = new TabPanelLayout($tab, [
                'classes' => [],
                'label' => $data['label'],
                'content' => new Widget([
                    'content' => new HtmlSnippet($this->getTabContent($experiment, $i))
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
    private function getTabContent(ExperimentType $experimentType, $tabIndex): string
    {

        $pageTitle = $this->context['page'];
        $subPage = $pageTitle->getText() . '/' . $experimentType->getBaseHeaderTemplate();
        $text = WikiTools::getText(Title::newFromText($subPage));

        $baseHeaderTemplate = $experimentType->getBaseHeaderTemplate();
        $baseRowTemplate = $experimentType->getBaseRowTemplate();
        $tab = $experimentType->getTab($tabIndex);
        $text = preg_replace("/$baseHeaderTemplate(\s|$)/", $tab['header-template'], $text);
        $text = preg_replace("/$baseRowTemplate(\s|$)/", $tab['row-template'], $text);


        $newText = $this->filterRows($text, $baseRowTemplate);

        $parser = new Parser();
        $parserOutput = $parser->parse($newText, $pageTitle, new ParserOptions());
        $html = $parserOutput->getText(['enableSectionEditLinks' => false]);
        if (WikiTools::isInVisualEditor() && $this->context['showEditLink']) {
            $htmlTableEditor = new HtmlTableEditor($html, $this->context['form']);
            return $htmlTableEditor->addEditButtonsAsFirstColumn();
        } else {
            return $html;
        }

    }

    /**
     * @param $text
     * @param $baseRowTemplate
     * @return string
     */
    private function filterRows($text, $baseRowTemplate): string
    {
        if (is_null($this->context['index'])) {
            return $text;
        }
        $templateParser = new TemplateParser($text);
        $root = new TemplateNode(true);
        $templateParser->parse($root);
        $root->removeNodes(function ($node) use ($baseRowTemplate) {
            if ($node instanceof TemplateNode) {
                return $node->getTemplateName() === $baseRowTemplate
                    && !in_array($node->getTemplateIndex(), $this->context['index']);
            }
            return false;
        });

        return $root->serialize();
    }


}
