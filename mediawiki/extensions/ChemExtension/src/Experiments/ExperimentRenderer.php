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
     * @param PanelLayout $form
     * @return PanelLayout or string
     * @throws Exception
     */
    public function renderInViewMode()
    {

        OutputPage::setupOOUI();

        $repo = ExperimentRepository::getInstance();
        $experiment = $repo->getExperimentType($this->context['form']);

        if ($experiment->hasOnlyOneTab() || WikiTools::isInVisualEditor()) {
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

        $templateParser = new TemplateParser($text);
        $root = $templateParser->parse();

        $this->filterRows($root, $experimentType->getBaseRowTemplate());
        $this->replaceTemplates($root, $experimentType, $tabIndex, $text);
        $text = $root->serialize();

        $parser = new Parser();
        $parserOutput = $parser->parse($text, $pageTitle, new ParserOptions());
        $html = $parserOutput->getText(['enableSectionEditLinks' => false]);
        return $this->addEditLinksIfNeeded($html);

    }

    /**
     * @param $root
     * @param $text
     * @param $baseRowTemplate
     * @return void
     */
    private function filterRows($root, $baseRowTemplate): void
    {
        if (is_null($this->context['index'])) {
            return;
        }
        $root->removeNodes(function ($node) use ($baseRowTemplate) {
            if ($node instanceof TemplateNode) {
                return $node->getTemplateName() === $baseRowTemplate
                    && !in_array($node->getTemplateIndex(), $this->context['index']);
            }
            return false;
        });

    }

    /**
     * @param $root
     * @param ExperimentType $experimentType
     * @param $tabIndex
     * @return void
     */
    private function replaceTemplates($root, ExperimentType $experimentType, $tabIndex): void
    {
        if (WikiTools::isInVisualEditor()) {
            return;
        }

        $root->visitNodes(function ($node) use ($experimentType, $tabIndex) {
            if ($node instanceof TemplateNode) {
                $tab = $experimentType->getTab($tabIndex);
                if ($node->getTemplateName() === $experimentType->getBaseRowTemplate()) {
                    $node->setTemplateName($tab['row-template']);
                } else if ($node->getTemplateName() === $experimentType->getBaseHeaderTemplate()) {
                    $node->setTemplateName($tab['header-template']);
                }
            }
        });
    }

    /**
     * @param string $html
     * @return false|string
     */
    private function addEditLinksIfNeeded(string $html)
    {
        if (!WikiTools::isInVisualEditor() || !$this->context['showEditLink']) {
            return $html;
        }
        $htmlTableEditor = new HtmlTableEditor($html, $this->context['form']);
        return $htmlTableEditor->addEditButtonsAsFirstColumn();
    }

}
