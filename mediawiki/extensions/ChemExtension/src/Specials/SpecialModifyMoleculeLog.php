<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\ModifyMoleculeLog;
use Exception;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\TextInputWidget;
use OutputPage;
use eftec\bladeone\BladeOne;
use SpecialPage;

class SpecialModifyMoleculeLog extends SpecialPage {
    function __construct()
    {
        parent::__construct('SpecialModifyMoleculeLog');

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        if (!is_writable($cache)) {
            throw new Exception("cache folder for blade engine is not writeable: $cache");
        }
        $this->blade = new BladeOne ($views, $cache);
        $this->logger = new LoggerUtils('SpecialModifyMoleculeLog', 'ChemExtension');
        $this->addedJobs = [];
    }

    /**
     * @throws \OOUI\Exception
     */
    function execute($par)
    {
        try {
            global $wgScriptPath;

            $output = $this->getOutput();
            $this->setHeaders();

            OutputPage::setupOOUI();
            $form = $this->createGUI();

            global $wgRequest;
            $moleculeLog = $this->processRequest($wgRequest);
            $html = $this->blade->run("show-molecule-modification-log", [
                'moleculeLog' => $moleculeLog,
                'form' => $form,
                'wgScriptPath' => $wgScriptPath
            ]);
            $this->getOutput()->addHTML($html);

        } catch (Exception $e) {
            $output->addHTML($this->showErrorHint($e->getMessage()));
        }
    }

    private function createGUI()
    {
        global $wgRequest, $wgScriptPath;

        $importButton = new ButtonInputWidget([
            'classes' => ['chemext-button'],
            'id' => 'chemext-show-log',
            'type' => 'submit',
            'label' => $this->msg('chemext-show-log-button')->text(),
            'flags' => ['primary', 'progressive'],
            'infusable' => true
        ]);



        $page = new FieldLayout(
            new TextInputWidget(['id' =>
                'chemext-molecule-id',
                'infusable' => true,
                'name' => 'molecule-id',
                'value' => $wgRequest->getText('molecule-id', ''),
                'placeholder' => $this->msg('molecule-id-hint')->plain(),

            ]),
            [
                'align' => 'top',
                'label' => $this->msg('molecule-id-label')->text()
            ]
        );


        return new FormLayout(['items' => [ $page, $importButton ],
            'method' => 'post',
            'action' => "$wgScriptPath/index.php/Special:" . $this->getName(),
            'enctype' => 'multipart/form-data',
        ]);
    }

    private function processRequest(\WebRequest $wgRequest)
    {
        $modificationLog = new ModifyMoleculeLog();
        $moleculeId = $wgRequest->getText('molecule-id', '');
        if ($moleculeId == '') {
            return [];
        }
        return $modificationLog->getLog($moleculeId);
    }

    protected function showErrorHint($message) {
        return $this->blade->run("error", ['message' => $message]);
    }
}
