<?php

namespace DIQA\ChemExtension\Experiments;

use DIQA\ChemExtension\ParserFunctions\ParserFunctionParser;
use DIQA\ChemExtension\Utils\ChemTools;
use DIQA\ChemExtension\Utils\HtmlTableEditor;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateParser;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateTextNode;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonInputWidget;
use ParserOptions;
use RequestContext;
use Title;

class ExperimentListRenderer extends ExperimentRenderer
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
        $experimentName = $this->context['name'];
        $pageTitle = $this->context['page'];
        $experimentPage = $pageTitle->getText() . '/' . $experimentName;
        $experimentPageTitle = Title::newFromText($experimentPage);
        if (!$experimentPageTitle->exists()) {
            throw new ExperimentNotExistsException("Experiment '$experimentPage' does not yet exist. Please click, and select 'Add/edit experiments' from the popup menu.", $experimentName);
        }

        $text = WikiTools::getText($experimentPageTitle);
        $templateParser = new TemplateParser($text);
        $ast = $templateParser->parse();
        $ast->visitNodes(function ($node) {
            if (!($node instanceof TemplateTextNode)) return;
            $params = explode('|', $node->getText());
            $keyValues = ParserFunctionParser::parseArguments($params);
            foreach ($keyValues as $key => $value) {
                $chemFormId = ChemTools::getChemFormIdFromPageTitle($value);
                if (!is_null($chemFormId)) {
                    $hooksContainer = MediaWikiServices::getInstance()->getHookContainer();
                    $hooksContainer->run('CollectMolecules', [$chemFormId, $this->context['page']]);
                }
            }
        });

        $cache = MediaWikiServices::getInstance()->getMainObjectStash();
        $cacheKey = $cache->makeKey('investigation-table', md5($text));
        $html = $cache->getWithSetCallback($cacheKey, $cache::TTL_DAY,
            function () use ($text, $pageTitle) {
                $parser = clone MediaWikiServices::getInstance()->getParser();
                $parserOutput = $parser->parse($text, $pageTitle, new ParserOptions(RequestContext::getMain()->getUser()));
                return $parserOutput->getText(['enableSectionEditLinks' => false]);
            });


        $htmlTableEditor = new HtmlTableEditor($html, $this->context);
        $htmlTableEditor->removeEmptyColumns();

        $htmlTableEditor->addIndexAsFirstColumn();
        if (!WikiTools::isInVisualEditor()) {
            $htmlTableEditor->removeOtherColumns("[@resource='include']");
        } else {
            $htmlTableEditor->addEditButtonsAsFirstColumn();
            // required because VE can handle only limited amount of HTML
            $htmlTableEditor->shortenTable(25);
        }

        $uniqueId = uniqid();
        $exportButton = new ButtonInputWidget([
            'classes' => ['chemext-button', 'experiment-link-export-button'],
            'id' => 'ce-export-investigation-' . $uniqueId,
            'type' => 'button',
            'label' => 'Export',
            'flags' => ['primary', 'progressive'],
            'title' => 'Export investigation as excel file',
            'infusable' => true,
            'value' => json_encode([
                'parameters' => [ 'form' => $this->context['form'],
                    'restrictToPages' => $this->context['page']->getPrefixedText(),
                    'onlyIncluded' => false
                 ],
                'selectExperimentQuery' => "",
                'page' => $this->context['page']->getPrefixedText(),
                'investigationPage' => $this->context['page']->getPrefixedText() . '/' . $this->context['name'],
                'type' => 'list'
            ])
        ]);

        $renameButton = new ButtonInputWidget([
            'classes' => ['chemext-button', 'experiment-list-rename-button'],
            'id' => 'ce-rename-investigation-' . $uniqueId,
            'type' => 'button',
            'label' => 'Rename',
            'flags' => ['primary', 'progressive'],
            'title' => 'Rename investigation page',
            'infusable' => true,
            'value' => json_encode([
                'page' => $this->context['page']->getPrefixedText(),
                'investigationName' => $this->context['name'],
            ])
        ]);

        $saveButton = new ButtonInputWidget([
            'classes' => ['chemext-button', 'experiment-list-save-button'],
            'id' => 'ce-save-investigation-' . $uniqueId,
            'type' => 'button',
            'label' => 'Save',
            'flags' => ['primary', 'progressive'],
            'title' => 'Save changes to investigation',
            'disabled' => true,
            'infusable' => true,
            'value' => json_encode([
                'investigationPageTitle' => $experimentPageTitle->getPrefixedText(),
                'investigationType' =>  $this->context['form'],
            ])
        ]);

        $refreshButton = new ButtonInputWidget([
            'classes' => ['chemext-button', 'experiment-list-refresh-button'],
            'id' => 'ce-refresh-investigation-' . $uniqueId,
            'type' => 'button',
            'label' => 'Refresh',
            'flags' => ['primary', 'progressive'],
            'title' => 'Refresh content investigations',
            'infusable' => true,
            'value' => json_encode([
                'parameters' => $this->context['parameters'] ?? [],
                'page' => $this->context['page']->getPrefixedText(),
                'cacheKey' => md5($text)
            ])
        ]);

        global $wgScriptPath;
        $htmlTableEditor->addTableClass("experiment-list");
        if (WikiTools::isInVisualEditor()) {
            $htmlTableEditor->removeTag("span[@class='smw-highlighter']");
        }
        return $this->blade->run("experiment-table", [
            'htmlTableEditor' => $htmlTableEditor,
            'experimentName' => $experimentName,
            'experimentPageTitle' => $experimentPageTitle,
            'description' => $this->context['description'],
            'inVisualEditor' => WikiTools::isInVisualEditor(),
            'wgScriptPath' => $wgScriptPath,
            'exportButton' => WikiTools::isInVisualEditor() ? '' : $exportButton->toString(),
            'refreshButton' => WikiTools::isInVisualEditor() ? '' : $refreshButton->toString(),
            'renameButton' => WikiTools::isInVisualEditor() || !$this->userHasMoveRights() ? '' : $renameButton->toString(),
            'saveButton' => WikiTools::isInVisualEditor() || !$this->userHasEditRights() ? '' : $saveButton->toString(),
        ]);

    }

    function userHasMoveRights() {
        return RequestContext::getMain()->getUser()->isAllowed('move');
    }

    function userHasEditRights() {
        return RequestContext::getMain()->getUser()->isAllowed('edit');
    }
}
