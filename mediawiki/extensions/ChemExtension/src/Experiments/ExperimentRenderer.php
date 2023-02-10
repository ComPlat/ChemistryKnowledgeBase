<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\TemplateParser\TemplateNode;
use DIQA\ChemExtension\TemplateParser\TemplateParser;
use DIQA\ChemExtension\Utils\HtmlTableEditor;
use DIQA\ChemExtension\Utils\QueryUtils;
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

    protected $blade;
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
        global $wgCEHiddenColumns;

        if ($wgCEHiddenColumns ?? false) {
            return $this->getTabContent()[''];
        } else {
            $tabsWithRenderedContent = $this->getTabContent();

            if (count($tabsWithRenderedContent) === 1 || WikiTools::isInVisualEditor()) {
                return $this->getTabContent()[''];
            }
        }

        $tabPanels = [];
        foreach ($tabsWithRenderedContent as $tabName => $html) {
            if ($tabName == '') continue;
            $tabPanels[] = new TabPanelLayout($tabName, [
                'classes' => [],
                'label' => $tabName,
                'content' => new Widget([
                    'content' => new HtmlSnippet($html)
                ]),
                'expanded' => false,
                'framed' => true,
            ]);

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
            'classes' => ['experimentlist-panel'],
            'content' => $indexLayout
        ]);

        return $form;

    }

    protected abstract function getTabContent(): array;



}
