<?php

namespace DIQA\ChemExtension\ChemScanner;

use Exception;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\SelectFileInputWidget;
use OutputPage;
use Philo\Blade\Blade;
use SpecialPage;

class ChemScannerSpecialpage extends SpecialPage
{

    private $blade;

    function __construct()
    {
        parent::__construct('SpecialUploadToChemscanner');

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ($views, $cache);

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );

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
                $createPages = $this->processUpload($tmpFolder);
                $output->addHTML("Created page: " . $createPages[0]);
                return;
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
        $filename = $_FILES["chemfile"]["name"] . '_' . uniqid();
        move_uploaded_file($_FILES["chemfile"]["tmp_name"], "$tmpFolder/$filename");
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
        //FIXME: for some reason /tmp folder is not writeable by apache
        //$tmpFolder = sys_get_temp_dir() . "/chemscanner";
        $tmpFolder = "/vagrant/chemscanner";

        if (!file_exists($tmpFolder)) {
            mkdir($tmpFolder);
        }
        if (!is_writable($tmpFolder)) {
            throw new \Exception("temporary uploadfolder $tmpFolder must be writeable. Please configure.");
        }
        return $tmpFolder;
    }
}
