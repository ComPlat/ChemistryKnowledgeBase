<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\Experiments\ExperimentXlsImporter;
use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Job;
use MediaWiki\MediaWikiServices;
use Title;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class ImportInvestigationJob extends Job
{

    private $logger;
    private $chemFormRepository;
    private $filePath;
    private $investigationType;
    private Title $investigationTitle;

    public function __construct($title, $params)
    {
        parent::__construct('ImportInvestigationJob', $params);
        $this->logger = new LoggerUtils('ImportInvestigationJob', 'ChemExtension');
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $this->chemFormRepository = new ChemFormRepository($dbr);
        $this->filePath = $params['filePath'];
        $this->investigationType = $params['investigationType'];
        $this->investigationTitle = $params['investigationTitle'];
    }

    public function run()
    {
        try {
            $reader = new Xlsx();
            $spreadsheet = $reader->load($this->filePath);
            if ($spreadsheet->sheetNameExists('ChemWiki')) {
                $sheet = $spreadsheet->getSheetByName('ChemWiki');
            } else {
                $sheet = $spreadsheet->getActiveSheet();
            }
            $importer = new ExperimentXlsImporter($sheet);
            $dataToImport = $importer->getDataToImport($this->investigationType);
            $this->logger->log(print_r($dataToImport, true));
            $importedMolecules = [];
            foreach ($dataToImport['nonExistingMolecules'] as $chemForm) {
                $chemFormId = $this->importMolecule($chemForm);
                $importedMolecules[$chemForm->getMoleculeKey()] = "Molecule:$chemFormId";
            }
            foreach($importedMolecules as $inchikey => $moleculeTitle) {
                $dataToImport['wikitext'] = str_replace($inchikey, $moleculeTitle, $dataToImport['wikitext']);
            }
            WikiTools::doEditContent($this->investigationTitle, $dataToImport['wikitext'], "auto-generated",
                $this->investigationTitle->exists() ? EDIT_UPDATE : EDIT_NEW);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function importMolecule(ChemForm $chemForm): int
    {
        $id = $this->chemFormRepository->addChemForm($chemForm->getMoleculeKey());
        $title = MoleculePageCreationJob::getPageTitleToCreate($id, $chemForm->getMolOrRxn());
        $jobParams = [];
        $jobParams['chemForm'] = $chemForm;
        $jobParams['parent'] = null;
        $jobParams['publicationPage'] = null;
        $job = new MoleculePageCreationJob($title, $jobParams);
        $job->run();
        $this->logger->log('Imported molecule with inchikey: ' . $chemForm->getMoleculeKey());
        return $id;
    }
}