<?php

namespace DIQA\ChemExtension\PublicationImport;

use DIQA\ChemExtension\Jobs\PublicationImportJob;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\SelectFileInputWidget;
use OOUI\TextInputWidget;
use OutputPage;
use Philo\Blade\Blade;
use RequestContext;
use SpecialPage;
use Title;

class PublicationImportSpecialpage extends SpecialPage
{

    private $blade;
    private $logger;

    function __construct()
    {
        parent::__construct('PublicationImportSpecialpage', 'edit');

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        if (!is_writable($cache)) {
            throw new Exception("cache folder for blade engine is not writeable: $cache");
        }
        $this->blade = new Blade ($views, $cache);
        $this->logger = new LoggerUtils('PublicationImportSpecialpage', 'ChemExtension');
    }

    /**
     * @throws \OOUI\Exception
     */
    function execute($par)
    {
        try {

            $output = $this->getOutput();
            $this->setHeaders();

            $user = RequestContext::getMain()->getUser();
            if ($user->isAnon()) {
                $output->addHTML('You must be logged-in and have at least the "edit"-right to use this feature.');
                return;
            }

            $tmpFolder = $this->checkPrerequisites();

            if (isset($_FILES["chemfile"]["name"])) {
                try {
                    global $wgRequest;
                    $pageTitle = $wgRequest->getText('page-title', '');
                    $doi = $wgRequest->getText('doi', '');
                    $uploadedFiles = $this->processUpload($tmpFolder);
                    $title = $this->createImportJobs($uploadedFiles, $pageTitle, $doi);
                    $this->putTitleOnWatchlist($title);
                    $output->addHTML($this->renderUploadResult($uploadedFiles));
                } catch (Exception $e) {
                    $output->addHTML($e->getMessage());
                }
                return;
            }

            OutputPage::setupOOUI();

            $output->addHTML($this->createHeader());
            $output->addHTML($this->createUploadForm());
            $output->addHTML($this->renderImportJobsList());

        } catch (\Exception $e) {
            $output->addHTML($e->getMessage());
        }
    }

    private function renderUploadResult($uploadedFiles)
    {
        global $wgServer, $wgScriptPath;

        return $this->blade->view()->make("publication-upload",
            [
                'wikiUrl' => "$wgServer$wgScriptPath/index.php",
                'uploadedFiles' => $uploadedFiles
            ]
        )->render();
    }

    /**
     * @return FormLayout
     * @throws \OOUI\Exception
     */
    private function createUploadForm(): FormLayout
    {
        global $wgScriptPath;
        $pageTitle = new FieldLayout(
            new TextInputWidget([
                'id' => 'chemext-page-title',
                'infusable' => true,
                'name' => 'page-title',
                'value' => '',
                'placeholder' => 'Page title'
            ]),
            [
                'align' => 'top',
                'label' => 'Page title'
            ]
        );

        $doi = new FieldLayout(
            new TextInputWidget([
                'id' => 'chemext-doi',
                'infusable' => true,
                'name' => 'doi',
                'value' => '',
                'placeholder' => 'DOI'
            ]),
            [
                'align' => 'top',
                'label' => 'DOI'
            ]
        );
        $fileWidget = new SelectFileInputWidget(['name' => 'chemfile[]', 'multiple' => true]);
        $uploadWidget = new FieldLayout(
            $fileWidget,
            [
                'align' => 'top',
                'label' => 'Select files to be processed by AI'
            ]
        );
        $submitButton = new ButtonInputWidget(['classes' => ['wfarm-button'],
            'id' => 'ce-upload-to-chemscanner',
            'type' => 'submit',
            'label' => $this->msg('ce-upload-to-chemscanner')->text(),
            'flags' => ['primary', 'progressive'],
            'infusable' => true]);
        $form = new FormLayout(['items' => [$pageTitle, $doi, $uploadWidget, $submitButton],
            'method' => 'post',
            'action' => "$wgScriptPath/index.php/Special:PublicationImportSpecialpage",
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
        $uploadedFiles = [];
        for ($i = 0; $i < count($_FILES["chemfile"]["name"]); $i++) {
            $name = $_FILES["chemfile"]["name"][$i];
            $tmpName = $_FILES["chemfile"]["tmp_name"][$i];
            $pathInfo = pathinfo($name);
            $filename = $pathInfo['filename'] . "_" . uniqid() . "." . $pathInfo['extension'];
            if (move_uploaded_file($tmpName, "$tmpFolder/$filename") === false) {
                throw new Exception("Can not store uploaded file at $tmpFolder/$filename");
            }

            $uploadedFiles[$name] = "$tmpFolder/$filename";
        }
        return $uploadedFiles;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function checkPrerequisites(): string
    {
        global $wgCEChemScannerTempFolder;
        $tmpFolder = sys_get_temp_dir() . "/pub-import";
        $tmpFolder = $wgCEChemScannerTempFolder ?? $tmpFolder;

        if (!file_exists($tmpFolder)) {
            mkdir($tmpFolder);
        }
        if (!is_writable($tmpFolder)) {
            throw new \Exception("temporary uploadfolder $tmpFolder must be writeable. Please configure.");
        }
        return $tmpFolder;
    }

    private function createImportJobs(array $uploadedFiles, $pageTitle, $doi): Title
    {
        $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
        $paths = array_values($uploadedFiles);
        $pageTitle = $pageTitle !== '' ? $pageTitle : array_keys($uploadedFiles)[0];
        $title = Title::newFromText($pageTitle);
        $job = new PublicationImportJob($title, ['paths' => $paths, 'doi' => $doi]);
        $jobQueue->push($job);
        return $title;
    }

    private function putTitleOnWatchlist(Title $title): void
    {
        $store = MediaWikiServices::getInstance()->getWatchedItemStore();
        $user = RequestContext::getMain()->getUser();
        if ($user->getEmail() === '' || !$user->canSendEmail()) {
            $this->logger->warn("User does not have email set or cannot send emails: " . $user->getName());
        }

        $store->removeWatch($user, $title);
        $store->addWatch($user, $title);

    }

    private function renderImportJobsList(): string
    {
        $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroup()->get('PublicationImportJob');
        $jobs = iterator_to_array($jobQueue->getAllQueuedJobs());
        return $this->blade->view()->make("publication-job-list",
            [
                'jobs' => $jobs
            ]
        )->render();
    }

    private function createHeader()
    {
        $html = <<<HTML
<div style="margin-bottom: 20px">
This page allows uploading of publications to be processed by AI. After processing, the publication page is automatically created. You are informed 
by email when the page is ready (if you specified one in your profile).
</div>  
HTML;
        return $html;
    }
}
