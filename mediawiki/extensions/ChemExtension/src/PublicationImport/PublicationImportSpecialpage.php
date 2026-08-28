<?php

namespace DIQA\ChemExtension\PublicationImport;

use DIQA\ChemExtension\Jobs\PublicationImportJob;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\PdfUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use DIQA\ChemExtension\Widgets\TitleMultiSelectWidget;
use eftec\bladeone\BladeOne;
use Exception;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\HiddenInputWidget;
use OOUI\SelectFileInputWidget;
use OOUI\TextInputWidget;
use OutputPage;
use RequestContext;
use SpecialPage;
use Title;

class PublicationImportSpecialpage extends SpecialPage
{

    private BladeOne $blade;
    private LoggerUtils $logger;
    private string $message = '';
    private string $status = '';

    /**
     * @throws Exception
     */
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
        $output = $this->getOutput();
        try {
            $this->setHeaders();
            $user = RequestContext::getMain()->getUser();
            if ($user->isAnon()) {
                $output->addHTML('You must be logged-in and have at least the "edit"-right to use this feature.');
                return;
            }

            try {
                if ($this->isUploadRequest()) {
                    $this->handleUploadRequest();
                    $this->getOutput()->redirect( RequestContext::getMain()->getTitle()->getFullURL() );
                    return;
                }

            } catch (DOIAlreadyExistsException $e) {
                $doi = $e->getDOI();
                $this->status = "OVERWRITE";
                $this->message = "Publication with DOI $doi already exists. Please confirm that it will be overwritten.";
            }
            OutputPage::setupOOUI();

            $output->addHTML($this->createHeader());
            $output->addHTML($this->renderMessageIfNecessary());
            $output->addHTML($this->createUploadForm());
            $output->addHTML($this->renderImportProcesses());

        } catch (Exception $e) {
            $this->message = $e->getMessage();
            $output->addHTML($this->renderMessageIfNecessary());

            $url = RequestContext::getMain()->getTitle()->getFullURL(RequestContext::getMain()->getRequest()->getPostValues());
            $output->addHTML(sprintf('<br><br><a href="%s">Go back to import page</a>',
                $url));
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
            $alreadyUploaded = count(PdfUtils::getPublicationPDFs($doi)) > 0;
            $uploading = $_FILES["chemfile"]["name"][0] !== '';
            if (!$alreadyUploaded && !$uploading) {
                throw new Exception('No files selected or already uploaded.');
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
                'default' => $presetTopic === '' ? [] : explode(",", $presetTopic),
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
            'id' => 'ce-import-publication',
            'type' => 'submit',
            'label' => $this->msg('ce-import-publication')->text(),
            'flags' => ['primary', $this->status === 'OVERWRITE' ? 'destructive' : 'progressive'],
            'infusable' => true]);

        $hiddenWidget = new HiddenInputWidget(['name' => 'confirm', 'value' => $this->status === 'OVERWRITE' ? 'true' : 'false' ]);

        if ($this->status === 'OVERWRITE') {
            $uploadWidget->toggle(false);
            $pageTitle->toggle(false);
            $topicCategory->toggle(false);
            $doi->toggle(false);
        }

        $form = new FormLayout(['items' => [$uploadWidget, $pageTitle, $topicCategory, $doi, $submitButton, $hiddenWidget],
            'method' => 'post',
            'action' => "$wgScriptPath/index.php/Special:PublicationImportSpecialpage",
            'enctype' => 'multipart/form-data',
        ]);
        return $form;
    }


    /**
     * @throws Exception
     */
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
            $size = $_FILES["chemfile"]["size"][$i];
            if ($size > 30 * 1024 * 1024) {
                throw new Exception("File size exceeds 30MB");
            }
            $name = $_FILES["chemfile"]["name"][$i];
            if ($name === '') {
                continue;
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

    private function createImportJobs(array $uploadedFiles, $displayTitle, $doi, array $topics): Title
    {
        $db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $repo = new ImportProcessRepository($db);
        $insertId = $repo->addImportProcess($displayTitle, $doi);

        $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
        $paths = array_values($uploadedFiles);
        $displayTitle = $displayTitle !== '' ? $displayTitle : array_keys($uploadedFiles)[0];
        $wikiTitle = WikiTools::makeWikiTitleFromDoi($doi);

        $job = new PublicationImportJob($wikiTitle, [
            'paths' => $paths,
            'doi' => $doi,
            'displayTitle' => $displayTitle,
            'topics' => $topics,
            'importProcessId' => $insertId
        ]);
        $jobQueue->push($job);

        return $wikiTitle;
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


    /**
     * @throws DOIAlreadyExistsException
     * @throws Exception
     */
    public function handleUploadRequest(): void
    {
        global $wgRequest;
        $output = $this->getOutput();


        $pageTitle = $wgRequest->getText('page-title');
        $doi = $wgRequest->getText('doi');
        $topics = $wgRequest->getText('topic', '');
        if ($topics === '') $topics = "Topic";
        $uploadedFiles = $this->processUpload($doi);
        if (count($uploadedFiles) === 0) {
            $pubFiles = PdfUtils::getPublicationPDFs($doi);
            foreach ($pubFiles as $pubFile) {
                $uploadedFiles[basename($pubFile)] = $pubFile;
            }
        }

        $forceOverwrite = $wgRequest->getText('confirm') === 'true';
        if (WikiTools::makeWikiTitleFromDoi($doi)->exists() && !$forceOverwrite) {
            throw new DOIAlreadyExistsException($doi);
        }

        $title = $this->createImportJobs($uploadedFiles, $pageTitle, $doi, explode("\n", $topics));
        $this->putTitleOnWatchlist($title);
        $output->addHTML($this->renderUploadResult($uploadedFiles));


    }

    private function renderMessageIfNecessary(): string
    {
        if (is_null($this->message)) {
            return '';
        }
        return $this->blade->run("error", ['message' => $this->message]);
    }


}
