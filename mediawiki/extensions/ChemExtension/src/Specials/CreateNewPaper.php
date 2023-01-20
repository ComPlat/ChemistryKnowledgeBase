<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\Literature\DOIResolver;
use Exception;
use MediaWiki\Widget\TitlesMultiselectWidget;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\TextInputWidget;
use OutputPage;
use Philo\Blade\Blade;
use Title;
use WebRequest;

class CreateNewPaper extends PageCreationSpecial
{

    private $blade;

    function __construct()
    {
        parent::__construct('CreateNewPaper');

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        if (!is_writable($cache)) {
            throw new Exception("cache folder for blade engine is not writeable: $cache");
        }
        $this->blade = new Blade ($views, $cache);

    }

    /**
     * @throws \OOUI\Exception
     */
    function execute($par)
    {
        try {

            $output = $this->getOutput();
            $this->setHeaders();

            global $wgRequest;
            global $wgRequest;
            if ($wgRequest->getMethod() == 'POST') {
                try {
                    $this->processRequest($wgRequest);
                    return;
                } catch (Exception $e) {
                    $this->getOutput()->addHTML($this->showErrorHint($e->getMessage()));
                }

            }


            OutputPage::setupOOUI();

            $form = $this->createGUI();
            $output->addHTML($form);

        } catch (\Exception $e) {
            $output->addHTML($e->getMessage());
        }
    }

    /**
     * @param string $wgScriptPath
     * @return FormLayout
     * @throws \OOUI\Exception
     */
    private function createGUI(): FormLayout
    {
        global $wgScriptPath, $wgRequest;

        $createPaperButton = new ButtonInputWidget([
            'classes' => ['chemext-button'],
            'id' => 'chemext-create-paper',
            'type' => 'submit',
            'label' => $this->msg('chemext-create-paper')->text(),
            'flags' => ['primary', 'progressive'],
            'infusable' => true
        ]);

        $paperTitle = new FieldLayout(
            new TextInputWidget([
                'id' => 'chemext-topic-title',
                'infusable' => true,
                'name' => 'paper-title',
                'value' => $wgRequest->getText('paper-title', ''),
                'placeholder' => $this->msg('paper-hint')
            ]),
            [
                'align' => 'top',
                'label' => $this->msg('paper-label')->text()
            ]
        );

        $topicCategory = new FieldLayout(
            new TitlesMultiselectWidget(['id' =>
                'chemext-topic-super',
                'infusable' => true,
                'name' => 'topic-super',
                'default' => $this->getPresetDataForTitleInput($wgRequest->getText('topic-super', '')),
                'placeholder' => $this->msg('topic-super-hint')->plain(),
                'classes' => ['chemtext-topic-input'],
            ]),
            [
                'align' => 'top',
                'label' => $this->msg('topic-super-label')->text()
            ]
        );

        $doiInput = new FieldLayout(
            new TextInputWidget(['id' => 'chemext-doi', 'name' => 'doi', 'placeholder' => $this->msg('doi-hint')]),
            [
                'align' => 'top',
                'label' => $this->msg('doi-label')->text() . " " . $this->msg('optional')->text()
            ]
        );

        $helpSection = $this->getHelpSection('Help:Create_new_paper');

        return new FormLayout(['items' => [$paperTitle, $topicCategory, $doiInput, $createPaperButton, $helpSection],
            'method' => 'post',
            'action' => "$wgScriptPath/index.php/Special:" . $this->getName(),
            'enctype' => 'multipart/form-data',
        ]);
    }

    private function processRequest(WebRequest $wgRequest)
    {
        $doi = $wgRequest->getText('doi', '');
        $paperTitle = $wgRequest->getText('paper-title', '');
        $topicSuper = $wgRequest->getText('topic-super', 'Topic');
        $doiData = null;
        if ($doi != '') {
            $doiResolver = new DOIResolver();
            $doiData = $doiResolver->resolve($doi);
        }
        if ($paperTitle != '') {
            $paperTitleObj = Title::newFromText($paperTitle);
        } else {
            throw new Exception("Paper title must be set.");
        }
        $this->createPageAndRedirect($paperTitleObj, $topicSuper, $doiData);
    }


}
