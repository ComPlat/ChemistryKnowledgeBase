<?php

namespace DIQA\ChemExtension\PublicationImport;

use DIQA\ChemExtension\Jobs\PublicationImportJob;
use DIQA\ChemExtension\Literature\DOITools;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\PdfUtils;
use DIQA\ChemExtension\Utils\QueryUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use DIQA\ChemExtension\Widgets\TitleMultiSelectWidget;
use eftec\bladeone\BladeOne;
use Exception;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\SelectFileInputWidget;
use OOUI\TextInputWidget;
use OutputPage;
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
        $this->blade = new BladeOne ($views, $cache);
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

            if ($this->isUploadRequest()) {
                $this->handleUploadRequest();
                return;
            }

            OutputPage::setupOOUI();

            $output->addHTML($this->createHeader());
            $output->addHTML($this->createUploadForm());
            $output->addHTML($this->renderImportProcesses());

        } catch (\Exception $e) {
            $output->addHTML($e->getMessage());
        }
    }

    private function isUploadRequest(): bool
    {
        global $wgRequest;
        $pageTitle = $wgRequest->getText('page-title', '');
        $doi = $wgRequest->getText('doi', '');
        if ($wgRequest->wasPosted()) {
            if ($doi === '') {
                throw new Exception('DOI is mandatory. Please specify one.');
            }
            if ($pageTitle === '') {
                throw new Exception('Publication title is mandatory. Please specify one.');
            }
            if (count($_FILES["chemfile"]["name"] ?? []) === 0) {
                throw new Exception('No files selected or filesize is too large. Max upload size is 30MB.');
            }
            return true;
        }
        $pubFiles = PdfUtils::getPublicationPDFs($doi);
        return count($pubFiles) > 0 && !empty($doi) && !empty($pageTitle);
    }

    private function renderUploadResult($uploadedFiles): string
    {
        global $wgServer, $wgScriptPath;

        return $this->blade->run("publication-upload",
            [
                'wikiUrl' => "$wgServer$wgScriptPath/index.php",
                'uploadedFiles' => $uploadedFiles
            ]
        );
    }

    private function renderImportProcesses(): string
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $repo = new ImportProcessRepository($dbr);
        $rows = $repo->getAllImportProcessesSince(date('Y-m-d', time() - 30 * 24 * 60 * 60));
        $renderer = new ImportProcessTableRenderer($rows);
        return $renderer->render();
    }

    /**
     * @return FormLayout
     * @throws \OOUI\Exception
     */
    private function createUploadForm(): FormLayout
    {
        global $wgScriptPath, $wgRequest;
        $pageTitle = new FieldLayout(
            new TextInputWidget([
                'id' => 'chemext-page-title',
                'infusable' => true,
                'name' => 'page-title',
                'value' => $wgRequest->getText('page-title', ''),
                'placeholder' => 'Publication title'
            ]),
            [
                'align' => 'top',
                'label' => 'Publication title (optional)'
            ]
        );

        $doi = new FieldLayout(
            new TextInputWidget([
                'id' => 'chemext-doi',
                'infusable' => true,
                'name' => 'doi',
                'value' => $wgRequest->getText('doi', ''),
                'placeholder' => 'DOI',
                'required' => true,
            ]),
            [
                'align' => 'top',
                'label' => 'DOI'
            ]
        );

        $presetTopic = $wgRequest->getText('topic', '');
        $topicCategory = new FieldLayout(
            new TitleMultiSelectWidget(['id' =>
                'chemext-topic',
                'infusable' => true,
                'name' => 'topic',
                'default' =>  $presetTopic === '' ? [] : explode(",", $presetTopic),
                'placeholder' => $this->msg('topic-super-hint')->plain(),
                'classes' => ['chemtext-topic-input'],
                'namespace' => NS_CATEGORY
            ]),
            [
                'align' => 'top',
                'label' => $this->msg('topic-super-label')->text()
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
        $form = new FormLayout(['items' => [$uploadWidget, $pageTitle, $topicCategory, $doi, $submitButton],
            'method' => 'post',
            'action' => "$wgScriptPath/index.php/Special:PublicationImportSpecialpage",
            'enctype' => 'multipart/form-data',
        ]);
        return $form;
    }


    private function processUpload(string $doi): array
    {
        $tmpFolder = PdfUtils::getPublicationPDFDirectory($doi);
        if (!file_exists($tmpFolder)) {
            mkdir($tmpFolder);
        }
        if (!is_writable($tmpFolder)) {
            throw new \Exception("temporary uploadfolder $tmpFolder must be writeable. Please configure.");
        }
        $this->logger->log("Processing upload for DOI $doi to $tmpFolder");
        $uploadedFiles = [];
        for ($i = 0; $i < count($_FILES["chemfile"]["name"] ?? []); $i++) {
            $name = $_FILES["chemfile"]["name"][$i];
            if ($name === '') {
                throw new Exception("No file(s) selected.");
            }
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

    private function createImportJobs(array $uploadedFiles, $pageTitle, $doi, array $topics): Title
    {
        $db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $repo = new ImportProcessRepository($db);
        $insertId = $repo->addImportProcess($pageTitle, $doi);

        $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
        $paths = array_values($uploadedFiles);
        $pageTitle = $pageTitle !== '' ? $pageTitle : array_keys($uploadedFiles)[0];
        $title = Title::newFromText(WikiTools::cleanTitle($pageTitle));
        $job = new PublicationImportJob($title, ['paths' => $paths, 'doi' => $doi, 'topics' => $topics, 'importProcessId' => $insertId]);
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


    private function createHeader(): string
    {
        $html = <<<HTML
<div style="margin-bottom: 20px">
This page allows uploading of publications to be processed by AI. After processing, the publication page is automatically created. You are informed 
by email when the page is ready (if you specified one in your profile).
</div>  
HTML;
        return $html;
    }

    private function checkIfDOIAlreadyExists($doi, $pageToImport): void
    {
        $doi = DOITools::parseDOI($doi);
        $results = QueryUtils::executeBasicQuery("[[DOI::$doi]]");
        $exists = $results->getCount() > 0;

        if ($exists) {
            $row = $results->getNext();
            $column = reset($row);
            $dataItem = $column->getNextDataItem();
            $pageTitle = $dataItem->getTitle();
            if ($pageTitle->getPrefixedText() === $pageToImport) {
                return;
            }
            $link = sprintf('<a href="%s">%s</a>', $pageTitle->getFullURL(), $pageTitle->getText());
            throw new Exception("Page for this DOI '$doi' already exists: $link");
        }
    }

    public function handleUploadRequest(): void
    {
        global $wgRequest;
        $output = $this->getOutput();

        try {
            $pageTitle = $wgRequest->getText('page-title');
            $doi = $wgRequest->getText('doi');
            $topics = $wgRequest->getText('topic', '');
            if ($topics === '') $topics = "Topic";

            $this->checkIfDOIAlreadyExists($doi, $pageTitle);
            $uploadedFiles = $this->processUpload($doi);
            if (count($uploadedFiles) === 0) {
                $pubFiles = PdfUtils::getPublicationPDFs($doi);
                foreach ($pubFiles as $pubFile) {
                    $uploadedFiles[basename($pubFile)] = $pubFile;
                }
            }
            $title = $this->createImportJobs($uploadedFiles, $pageTitle, $doi, explode("\n", $topics));
            $this->putTitleOnWatchlist($title);
            $output->addHTML($this->renderUploadResult($uploadedFiles));
        } catch (Exception $e) {
            $output->addHTML($e->getMessage());
            $output->addHTML(sprintf('<br><br><a href="%s">Go back to import page</a>',
                RequestContext::getMain()->getTitle()->getFullURL()));
        }

    }
}
