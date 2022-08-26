<?php

namespace DIQA\ChemExtension\MoleculeRGroupBuilder;

use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\InchIGenerator;
use DIQA\ChemExtension\Pages\PageCreator;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Job;
use MediaWiki\MediaWikiServices;
use Title;
use Exception;

class MoleculesImportJob extends Job
{

    private $client;
    private $publicationPage;
    private $inchiGenerator;

    public function __construct($title, $params)
    {
        parent::__construct('MoleculesImportJob', $title, $params);

        global $wgCEUseMoleculeRestsClientMock;
        $this->client = $wgCEUseMoleculeRestsClientMock ? new MoleculeRGroupServiceClientMock()
            : new MoleculeRGroupServiceClientImpl();
        $this->publicationPage = $title;
        $this->inchiGenerator = new InchIGenerator();
    }

    public function run()
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );

        $chemFormRepo = new ChemFormRepository($dbr);
        $chemFormRepo->deleteAllConcreteMolecule($this->publicationPage);
        $pageCreator = new PageCreator();

        foreach ($this->params['moleculeCollections'] as $collection) {

            $logger = new LoggerUtils('MoleculesImportJob', 'ChemExtension');
            $restsTransposed = ArrayTools::transpose($collection['chemForm']->getRests());

            try {
                $response = $this->client->buildMolecules($collection['chemForm']->getMolOrRxn(), $restsTransposed);
                $molecules = $response->molecules;
                foreach ($molecules as $molecule) {
                    $inchi = $this->getInchi($molecule->molfile);
                    $chemForm = ChemForm::fromMolOrRxn($molecule->molfile, $inchi['InChI'], $inchi['InChIKey']);

                    $title = $pageCreator->createNewMoleculePage($chemForm, $collection['title']);
                    $logger->log("Created molecule/reaction page: {$title->getPrefixedText()}, "
                        . "molfile: {$chemForm->getMolOrRxn()}, chemFormId: {$chemForm->getMoleculeKey()}");
                    $chemFormRepo->addConcreteMolecule($this->publicationPage, $collection['title'],
                        $title, $collection['chemForm']->getDatabaseId(), $molecule->rests);

                }
            } catch (Exception $e) {
                $logger->error($e->getMessage());
            }

        }
    }

    private function getInchi($molfile) {
        global $wgCEUseMoleculeRestsClientMock;
        $mock = $wgCEUseMoleculeRestsClientMock ?? false;
        if ($mock) {
            return ['InChI' => md5(uniqid()), 'InChIKey' => md5(uniqid())];
        } else {
            return $this->inchiGenerator->getInchI($molfile);
        }
    }
}