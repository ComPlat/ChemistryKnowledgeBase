<?php

namespace DIQA\ChemExtension\MoleculeRestBuilder;

use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\InchIGenerator;
use DIQA\ChemExtension\Pages\PageCreator;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Job;
use Exception;

class MoleculesImportJob extends Job
{


    private $client;

    public function __construct($title, $params)
    {
        parent::__construct('MoleculesImportJob', $title, $params);

        global $wgCEUseMoleculeRestsClientMock;
        $this->client = $wgCEUseMoleculeRestsClientMock ? new MoleculeRestServiceClientMock()
            : new MoleculeRestServiceClientImpl();
    }

    public function run()
    {
        $formula = $this->params['formula'];
        $rests = $this->params['rests'];
        $logger = new LoggerUtils('MoleculesImportJob', 'ChemExtension');
        $restsTransposed = ArrayTools::transpose($rests);

        try {
            $chemForms = [];
            $response = $this->client->buildMolecules($formula, $restsTransposed);
            $molecules = $response->molecules;
            foreach ($molecules as $molecule) {
                $inchiGenerator = new InchIGenerator();
                $inchi = $inchiGenerator->getInchI($molecule->molfile);
                $chemForms[] = ChemForm::fromMolOrRxn($molecule->molfile, $inchi['InChI'], $inchi['InChIKey']);
            }
        } catch (Exception $e) {
            $logger->error($e->getMessage());
        }

        $pageCreator = new PageCreator();
        foreach ($chemForms as $chemForm) {
            try {
                $title = $pageCreator->createNewMoleculePage($chemForm);
                $logger->log("Created molecule/reaction page: {$title->getPrefixedText()}, "
                    ."molfile: {$chemForm->getMolOrRxn()}, chemFormId: {$chemForm->getChemFormId()}");
            } catch (Exception $e) {
                $logger->error($e->getMessage());
            }
        }

    }


}