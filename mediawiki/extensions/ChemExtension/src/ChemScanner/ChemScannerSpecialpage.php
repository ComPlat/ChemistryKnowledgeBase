<?php

namespace DIQA\ChemExtension\ChemScanner;

use Exception;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\SelectFileInputWidget;
use OutputPage;
use eftec\bladeone\BladeOne;
use SpecialPage;

class ChemScannerSpecialpage extends SpecialPage
{

    private $blade;

    function __construct()
    {
        parent::__construct('SpecialUploadToChemscanner');

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

            $tmpFolder = $this->checkPrerequisites();

            if (isset($_FILES["chemfile"]["name"])) {
                try {
                    $createPages = $this->processUpload($tmpFolder);
                    $output->addHTML($this->renderChemScannerUploadResult($createPages[0]));
                } catch(Exception $e) {
                    $output->addHTML($e->getMessage());
                }
                return;
            }

            OutputPage::setupOOUI();

            $form = $this->createGUI();
            $output->addHTML($form);

        } catch (\Exception $e) {
            $output->addHTML($e->getMessage());
        }
    }

    private function renderChemScannerUploadResult($jobId)
    {
        global $wgServer, $wgScriptPath;

        return $this->blade->run("chemscanner-upload",
            ['wikiUrl' => "$wgServer$wgScriptPath/index.php",
                'jobId' => $jobId
            ]
        );
    }

    /**
     * @param string $wgScriptPath
     * @return FormLayout
     * @throws \OOUI\Exception
     */
    private function createGUI(): FormLayout
    {
        global $wgScriptPath;
        $fileWidget = new SelectFileInputWidget(['name' => 'chemfile']);
        $uploadWidget = new FieldLayout(
            $fileWidget,
            [
                'align' => 'top',
                'label' => $this->msg('ce-choose-file')->text()
            ]
        );
        $submitButton = new ButtonInputWidget(['classes' => ['wfarm-button'],
            'id' => 'ce-upload-to-chemscanner',
            'type' => 'submit',
            'label' => $this->msg('ce-upload-to-chemscanner')->text(),
            'flags' => ['primary', 'progressive'],
            'infusable' => true]);
        $form = new FormLayout(['items' => [$uploadWidget, $submitButton],
            'method' => 'post',
            'action' => "$wgScriptPath/index.php/Special:SpecialUploadToChemscanner",
            'enctype' => 'multipart/form-data',
        ]);
        return $form;
    }

    /**
     * @param string $tmpFolder
     * @return array
     * @throws \Exception
     */
    private function processUpload(string $tmpFolder): array
    {
        $pathInfo = pathinfo($_FILES["chemfile"]["name"]);
        $filename = $pathInfo['filename'] . "_" . uniqid() . "." . $pathInfo['extension'];
        if (move_uploaded_file($_FILES["chemfile"]["tmp_name"], "$tmpFolder/$filename") === false) {
            throw new Exception("Can not store uploaded file at $tmpFolder/$filename");
        }
        $chreq = new ChemScannerRequest("$tmpFolder/$filename");
        $createPages = $chreq->send();
        return $createPages;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function checkPrerequisites(): string
    {
        global $wgCEChemScannerTempFolder;
        $tmpFolder = sys_get_temp_dir() . "/chemscanner";
        $tmpFolder = $wgCEChemScannerTempFolder ?? $tmpFolder;

        if (!file_exists($tmpFolder)) {
            mkdir($tmpFolder);
        }
        if (!is_writable($tmpFolder)) {
            throw new \Exception("temporary uploadfolder $tmpFolder must be writeable. Please configure.");
        }
        return $tmpFolder;
    }
}
