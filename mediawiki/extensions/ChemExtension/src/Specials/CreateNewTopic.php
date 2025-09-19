<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\Widgets\TitleMultiSelectWidget;
use Exception;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\TextInputWidget;
use OutputPage;
use eftec\bladeone\BladeOne;
use Title;

class CreateNewTopic extends PageCreationSpecial
{

    private $blade;

    function __construct()
    {
        parent::__construct('CreateNewTopic');

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        if (!is_writable($cache)) {
            throw new Exception("cache folder for blade engine is not writeable: $cache");
        }
        $this->blade = new BladeOne ($views, $cache);

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
            'id' => 'chemext-create-topic',
            'type' => 'submit',
            'label' => $this->msg('chemext-create-topic')->text(),
            'flags' => ['primary', 'progressive'],
            'infusable' => true
        ]);

        $topicTitle = new FieldLayout(
            new TextInputWidget([
                'id' => 'chemext-topic-title',
                'infusable' => true,
                'name' => 'topic-title',
                'value' => $wgRequest->getText('topic-title', ''),
                'placeholder' => $this->msg('topic-hint')
            ]),
            [
                'align' => 'top',
                'label' => $this->msg('topic-label')->text()
            ]
        );

        $topicCategory = new FieldLayout(
            new TitleMultiSelectWidget(['id' =>
                'chemext-topic-super',
                'infusable' => true,
                'name' => 'topic-super',
                'default' => $this->getPresetDataForTitleInput($wgRequest->getText('topic-super', '')),
                'placeholder' => $this->msg('topic-super-hint')->plain(),
                'classes' => ['chemtext-topic-input'],
                'namespace' => NS_CATEGORY
            ]),
            [
                'align' => 'top',
                'label' => $this->msg('topic-super-label')->text()
            ]
        );


        $helpSection = $this->getHelpSection('Help:Create_new_topic');


        return new FormLayout(['items' => [$topicTitle, $topicCategory, $createPaperButton, $helpSection],
            'method' => 'post',
            'action' => "$wgScriptPath/index.php/Special:" . $this->getName(),
            'enctype' => 'multipart/form-data',
        ]);
    }

    /**
     * @param $wgRequest
     */
    private function processRequest($wgRequest): void
    {
        $topicTitle = $wgRequest->getText('topic-title', '');
        if ($topicTitle == '') {
            throw new Exception("Topic title must be set.");
        }
        if (strpos($topicTitle, '/') !== false
            || strpos($topicTitle, '#') !== false
            || strpos($topicTitle, '[') !== false
            || strpos($topicTitle, ']') !== false) {
            throw new Exception('These characters are not allowed in topic titles: /,#,[,]');
        }
        $topicTitleObj = Title::newFromText($topicTitle, NS_CATEGORY);
        $topicSuper = $wgRequest->getText('topic-super');
        if (is_null($topicSuper) || $topicSuper === '') {
            $topicSuper = 'Topic';
        }
        $this->createPageAndRedirect($topicTitleObj, $topicSuper);

    }


}
