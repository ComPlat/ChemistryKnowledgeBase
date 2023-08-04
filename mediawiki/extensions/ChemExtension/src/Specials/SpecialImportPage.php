<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\Pages\PageImportJob;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use JobQueueGroup;
use MediaWiki\Widget\TitlesMultiselectWidget;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OutputPage;
use Philo\Blade\Blade;
use Title;
use WebRequest;

class SpecialImportPage extends PageCreationSpecial
{

    private $blade;
    private $logger;

    function __construct()
    {
        parent::__construct('SpecialImportPage');

        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        if (!is_writable($cache)) {
            throw new Exception("cache folder for blade engine is not writeable: $cache");
        }
        $this->blade = new Blade ($views, $cache);
        $this->logger = new LoggerUtils('SpecialImportPage', 'ChemExtension');
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
                    $this->getOutput()->addHTML('<p>Import jobs were successfully created.</p>');
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
    private function createGUI()
    {
        global $wgScriptPath;
        global $wgDBname;
        if ($wgDBname == "chemmain") {
            return 'This is the main wiki. You cannot import from here';
        }
        $importButton = new ButtonInputWidget([
            'classes' => ['chemext-button'],
            'id' => 'chemext-create-paper',
            'type' => 'submit',
            'label' => $this->msg('chemext-import-page')->text(),
            'flags' => ['primary', 'progressive'],
            'infusable' => true
        ]);



        $page = new FieldLayout(
            new TitlesMultiselectWidget(['id' =>
                'special-import-page',
                'infusable' => true,
                'name' => 'pagetitle',
                'placeholder' => $this->msg('page-title-hint')->plain(),
                'classes' => ['chemtext-page-title-input'],
            ]),
            [
                'align' => 'top',
                'label' => $this->msg('page-title-label')->text()
            ]
        );


        return new FormLayout(['items' => [ $page, $importButton ],
            'method' => 'post',
            'action' => "$wgScriptPath/index.php/Special:" . $this->getName(),
            'enctype' => 'multipart/form-data',
        ]);
    }

    private function processRequest(WebRequest $wgRequest)
    {

        $pageTitles = explode("\n", $wgRequest->getText('pagetitle', ''));

        foreach($pageTitles as $pageTitle) {
            if ($pageTitle == '') {
                throw new Exception("Page must not be empty");
            }
            $pageTitleObj = Title::newFromText($pageTitle);
            if (!$pageTitleObj->exists()) {
                throw new Exception("Page does not exist");
            }

            $text = WikiTools::getText($pageTitleObj);
            if ($text == '') {
                $this->logger->warn("Page contains no text: $pageTitle. Skip it.");
                continue;
            }
            $job = new PageImportJob($pageTitleObj, ['wikitext' => $text]);
            JobQueueGroup::singleton()->push($job);
            $subPages = $pageTitleObj->getSubpages();
            foreach ($subPages as $subPage) {
                $job = new PageImportJob($subPage, ['wikitext' => WikiTools::getText($subPage)]);
                JobQueueGroup::singleton()->push($job);
            }
        }
    }


}
