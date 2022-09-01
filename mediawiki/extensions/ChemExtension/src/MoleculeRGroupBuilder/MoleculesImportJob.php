<?php

namespace DIQA\ChemExtension\MoleculeRGroupBuilder;

use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\InchIGenerator;
use DIQA\ChemExtension\Pages\MoleculePageCreator;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;
use Job;
use MediaWiki\MediaWikiServices;

class MoleculesImportJob extends Job
{

    private $client;
    private $publicationPage;
    private $inchiGenerator;

    public function __construct($title, $params)
    {
        parent::__construct('MoleculesImportJob', $title, $params);

        global $wgCEUseMoleculeRGroupsClientMock;
        $this->client = $wgCEUseMoleculeRGroupsClientMock ? new MoleculeRGroupServiceClientMock()
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
        $pageCreator = new MoleculePageCreator();

        foreach ($this->params['moleculeCollections'] as $collection) {

            $logger = new LoggerUtils('MoleculesImportJob', 'ChemExtension');
            $rGroupsTransposed = ArrayTools::transpose($collection['chemForm']->getRGroups());

            try {

                $molecules = $this->client->buildMolecules($collection['chemForm']->getMolOrRxn(), $rGroupsTransposed);

                foreach ($molecules as $molecule) {

                    $chemForm = ChemForm::fromMolOrRxn($molecule->mdl, $molecule->smiles, $molecule->inchi, $molecule->inchikey);
                    if (is_null($molecule->inchikey) || $molecule->inchikey === '') {
                        $logger->error("Can not create molecule page. Inchikey is empty. {$chemForm->__toString()}");
                        continue;
                    }

                    $title = $pageCreator->createNewMoleculePage($chemForm, $collection['title']);
                    $logger->log("Created molecule/reaction page: {$title->getPrefixedText()}, "
                        . "chemform: {$chemForm->__toString()}, moleculeKey: {$chemForm->getMoleculeKey()}");

                    $moleculeCollectionId = $chemFormRepo->getChemFormId($collection['chemForm']->getMoleculeKey());

                    $chemFormRepo->addConcreteMolecule($this->publicationPage, $collection['title'],
                        $title, $moleculeCollectionId, $this->makeRGroupsLowercase($molecule));

                }
            } catch (Exception $e) {
                $logger->error($e->getMessage());
            }

        }
    }

    private function makeRGroupsLowercase($molecule) {
        $result = [];
        $arr = ArrayTools::propertiesToArray($molecule);
        foreach($arr as $key => $value) {
            if (preg_match("/^r\d+/i", $key)) {
                $result[strtolower($key)] = $value;
            }
        }
        return $result;
    }

}