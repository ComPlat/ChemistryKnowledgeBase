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
            return $this->getTabContent($this->context['form'], 0);
        }

        $tabPanels = [];
        $i = 1;
        foreach ($experiment->getTabs() as $tab) {

            $tabPanels[] = new TabPanelLayout($tab, [
                'classes' => [],
                'label' => $tab,
                'content' => new Widget([
                    'content' => new HtmlSnippet($this->getTabContent($this->context['form'], $i))
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
    private function getTabContent($formName, $tabIndex): string
    {

        $pageTitle = $this->context['page'];
        $subPage = $pageTitle->getText() . '/' . $formName;
        $text = WikiTools::getText(Title::newFromText($subPage));

        $parser = new Parser();
        $parserOutput = $parser->parse($text, $pageTitle, new ParserOptions());
        $html = $parserOutput->getText(['enableSectionEditLinks' => false]);

        $htmlTableEditor = new HtmlTableEditor($html, $this->context['form']);
        if (!WikiTools::isInVisualEditor()) {
            $htmlTableEditor->removeOtherColumns($tabIndex);
        }
        if (!is_null($this->context['index'])) {
            $htmlTableEditor->retainRows($this->context['index']);
        }
        if (WikiTools::isInVisualEditor() && $this->context['showEditLink']) {
            $htmlTableEditor->addEditButtonsAsFirstColumn();
        }
        return $htmlTableEditor->toHtml();

    }


}
