<?php

namespace DIQA\ChemExtension\Specials;

use DIQA\ChemExtension\Jobs\ImportFromPubChem;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use MediaWiki\MediaWikiServices;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\FormLayout;
use OOUI\MultilineTextInputWidget;
use OutputPage;
use Philo\Blade\Blade;
use SpecialPage;
use WebRequest;

class ImportMolecule extends SpecialPage
{
    private $blade;


    public function __construct()
    {
        parent::__construct('ImportMolecule');
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $this->blade = new Blade ($views, $cache);

    }

    /**
     * @throws \OOUI\Exception
     */
    function execute($par)
    {
        global $wgRequest;
        try {

            $output = $this->getOutput();
            $this->setHeaders();

            if ($wgRequest->getMethod() == 'POST') {
                try {
                    $this->processRequest($wgRequest);
                    $this->getOutput()->addHTML('Import jobs created');
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
     * @return FormLayout
     * @throws \OOUI\Exception
     */
    private function createGUI()
    {
        global $wgRequest;
        global $wgScriptPath;
        $importButton = new ButtonInputWidget([
            'classes' => ['chemext-button'],
            'id' => 'chemext-import-molecule',
            'type' => 'submit',
            'label' => 'Create import jobs',
            'flags' => ['primary', 'progressive'],
            'infusable' => true
        ]);

        $paperTitle = new FieldLayout(
            new MultilineTextInputWidget([
                'id' => 'chemext-molecule_inchikeys',
                'infusable' => true,
                'name' => 'molecule_inchikeys',
                'value' => '',
                'placeholder' => 'InChI-Keys...'
            ]),
            [
                'align' => 'top',
                'label' => 'InChI-Keys (one per line)'
            ]
        );


        return new FormLayout(['items' => [$paperTitle, $importButton],
            'method' => 'post',
            'action' => "$wgScriptPath/index.php/Special:" . $this->getName(),
            'enctype' => 'multipart/form-data',
        ]);
    }

    private function processRequest(WebRequest $wgRequest)
    {
        $jobQueue = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $chemFormRepository = new ChemFormRepository($dbr);
        $inchiKeys = $wgRequest->getText('molecule_inchikeys', '');
        $inchiKeys = preg_split('/\n|\r\n/', $inchiKeys);
        $inchiKeys = array_map(fn($e) => trim($e), $inchiKeys);
        $inchiKeys = array_filter($inchiKeys, fn($e) => $e !== '' && is_null($chemFormRepository->getChemFormId($e)));

        foreach($inchiKeys as $inchiKey) {
            $job = new ImportFromPubChem($this->getPageTitle(), ['inchiKey' => $inchiKey]);
            $jobQueue->push($job);
        }

    }

    private function showErrorHint(string $message)
    {
        return $this->blade->view()->make("error", ['message' => $message])->render();
    }


}