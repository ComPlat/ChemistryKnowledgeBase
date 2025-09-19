<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\Experiments\ExperimentXlsImporter;
use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Utils\LoggerUtils;
use DIQA\ChemExtension\Utils\TemplateParser\TemplateParser;
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
            $rowsContent = $dataToImport['rowsContent'];

            if ($this->investigationTitle->exists()) {
                $currentText = WikiTools::getText($this->investigationTitle);
                $rowsContent = $this->mergeContent($currentText, $rowsContent);
            }
            $wikitext = str_replace('__ROWS_CONTENT__', $rowsContent, $dataToImport['wikitext']);
            WikiTools::doEditContent($this->investigationTitle, $wikitext, "auto-generated",
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

    private function mergeContent(string $currentText, string $rowsContent)
    {
        $tp = new TemplateParser($currentText);
        $root = $tp->parse();
        $rowNodes = $root->getFirstChild()->getNonTextNodes();
        $currentRowsText = join("\n", array_map(fn($n) => $n->serialize(), $rowNodes));
        return "$currentRowsText\n$rowsContent";
    }
}