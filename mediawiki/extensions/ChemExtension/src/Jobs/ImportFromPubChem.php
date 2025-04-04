<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\Indigo\IndigoTool;
use DIQA\ChemExtension\MoleculeRenderer\MoleculeRendererClientImpl;
use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\PubChem\PubChemService;
use DIQA\ChemExtension\Utils\LoggerUtils;
use MediaWiki\MediaWikiServices;
use Job;
use Exception;

class ImportFromPubChem extends Job
{

    private $logger;
    private $chemFormRepository;
    private $inchiKey;
    private $chemFormId;

    public function __construct($title, $params)
    {
        parent::__construct('ImportFromPubChem', $params);
        $this->logger = new LoggerUtils('ImportFromPubChem', 'ChemExtension');
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $this->chemFormRepository = new ChemFormRepository($dbr);
        $this->inchiKey = $params['inchiKey'];
    }

    public function run()
    {
        try {
            $pubChemService = new PubChemService();
            $metadata = $pubChemService->getPubChem($this->inchiKey);
            $smiles = $metadata['record']->getSMILES();
            $molfile = IndigoTool::convertSmilesToMolfile($smiles);

            $renderService = new MoleculeRendererClientImpl();
            $renderedMolecule = $renderService->render($molfile);

            $id = $this->chemFormRepository->addOrUpdateChemFormImage($this->inchiKey, base64_encode($renderedMolecule->svg));
            $title = MoleculePageCreationJob::getPageTitleToCreate($id, $molfile);
            $jobParams = [];
            $jobParams['chemForm'] = ChemForm::fromMolOrRxn($molfile, $smiles, '', $this->inchiKey);
            $jobParams['parent'] = null;
            $jobParams['publicationPage'] = null;
            $job = new MoleculePageCreationJob($title, $jobParams);
            $job->run();
            $this->chemFormId = $id;
            $this->logger->log("Imported molecule ($id) with inchikey: " . $this->inchiKey);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @return mixed
     */
    public function getChemFormId()
    {
        return $this->chemFormId;
    }

}