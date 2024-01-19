<?php

namespace DIQA\ChemExtension\Jobs;

use DIQA\ChemExtension\MoleculeRenderer\MoleculeRendererClientImpl;
use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientImpl;
use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientMock;
use DIQA\ChemExtension\Pages\ChemForm;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\Pages\MoleculePageCreator;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;
use Job;
use Title;
use MediaWiki\MediaWikiServices;

class RGroupMaterializationJob extends Job
{

    private $logger;
    private $rGroupClient;
    private $publicationPageTitle;
    private $pageCreator;
    private $moleculeRendererClient;
    private $chemFormRepo;
    private $concreteMolecules;

    public function __construct($title, $params)
    {
        parent::__construct('RGroupMaterializationJob', $title, $params);

        global $wgCEUseMoleculeRGroupsClientMock;
        $this->rGroupClient = $wgCEUseMoleculeRGroupsClientMock ? new MoleculeRGroupServiceClientMock()
            : new MoleculeRGroupServiceClientImpl();
        $this->publicationPageTitle = $title;
        $this->pageCreator = new MoleculePageCreator();
        $this->moleculeRendererClient = new MoleculeRendererClientImpl();
        $this->logger = new LoggerUtils('RGroupMaterializationJob', 'ChemExtension');
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $this->chemFormRepo = new ChemFormRepository($dbr);
        $this->concreteMolecules = $this->chemFormRepo->getAllConcreteMolecule($title);
    }

    public function run()
    {
        $this->chemFormRepo->deleteAllConcreteMolecule($this->publicationPageTitle);

        foreach ($this->params['moleculeCollections'] as $collection) {
            $this->importMoleculeCollection($collection);
        }
    }

    /**
     * Imports a molecule collection
     *
     * @param $collection array of ['chemform' => .., 'title' => ... ]
     */
    private function importMoleculeCollection(array $collection): void
    {
        try {

            $moleculeCollection = $collection['chemForm'];
            $rGroupsTransposed = ArrayTools::transpose($moleculeCollection->getRGroups());
            $concreteMoleculeResults = $this->rGroupClient->buildMolecules($moleculeCollection->getMolOrRxn(), $rGroupsTransposed);
            foreach ($concreteMoleculeResults as $m) {

                $concreteMolecule = $m['chemForm'];
                $rGroups = $m['rGroups'];
                if (is_null($concreteMolecule->getInchiKey()) || $concreteMolecule->getInchiKey() === '') {
                    $this->logger->error("Can not create molecule page. Inchikey is empty. {$concreteMolecule->__toString()}");
                    continue;
                }
                $existingMolecule = $this->getConcreteMoleculeWithRGroup($rGroups);

                if ($existingMolecule !== false) {
                    // concrete molecule already exists, so change it
                    $this->updateExistingMolecule($existingMolecule['molecule_page_id'], $collection, $concreteMolecule, $rGroups);
                } else {

                    $this->createMoleculePage($concreteMolecule, $collection, $rGroups);
                    $this->renderMolecule($concreteMolecule);
                }
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Creates a page for the concrete molecule and add it to repo
     *
     * @param ChemForm $concreteMolecule
     * @param array $collection Molecule collection ['chemform' => .., 'title' => ... ]
     * @param array $rGroups RGroups [ 'r1' => ..., 'r2' => ..., ... ]
     * @throws Exception
     */
    private function createMoleculePage(ChemForm $concreteMolecule, array $collection, array $rGroups): void
    {
        $moleculeCollection = $collection['chemForm'];
        $moleculeCollectionTitle = $collection['title'];
        $result = $this->pageCreator->createNewMoleculePage($concreteMolecule, $moleculeCollectionTitle, false);
        $concreteMoleculeTitle = $result['title'];
        $this->logger->log("Created molecule/reaction page: {$concreteMoleculeTitle->getPrefixedText()}, "
            . "chemform: {$concreteMolecule->__toString()}, moleculeKey: {$concreteMolecule->getMoleculeKey()}");

        $moleculeCollectionId = $this->chemFormRepo->getChemFormId($moleculeCollection->getMoleculeKey());
        $this->chemFormRepo->addConcreteMolecule($this->publicationPageTitle, $moleculeCollectionTitle,
            $concreteMoleculeTitle, $moleculeCollectionId, $rGroups);
    }

    /**
     * Renders the molecule server-side and stores the image in DB. Fails silently, just log the error.
     * If the molecule image already exists, it is not updated because it MUST be the same.
     *
     * @param ChemForm $concreteMolecule
     */
    private function renderMolecule(ChemForm $concreteMolecule): void
    {
        try {
            $renderedMolecule = $this->moleculeRendererClient->render($concreteMolecule->getMolOrRxn());
            $image = $this->chemFormRepo->getChemFormImageByKey($concreteMolecule->getMoleculeKey());
            if (is_null($image) || $image === '') {
                $this->chemFormRepo->addOrUpdateChemFormImage($concreteMolecule->getMoleculeKey(), base64_encode($renderedMolecule->svg));
                $this->logger->log("Rendered molecule SVG: " . $renderedMolecule->svg);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function getConcreteMoleculeWithRGroup($rGroups)
    {
        $find = array_filter($this->concreteMolecules, function($e) use($rGroups) {
                    return json_encode($e['rGroups']) === json_encode($rGroups);
            });
        return reset($find);
    }

    /**
     * @param $molecule_page_id
     * @param $concreteMolecule
     */
    private function updateExistingMolecule($molecule_page_id, $collection, ChemForm $concreteMolecule, $rGroups): void
    {
        // is null if molecule does not already exist
        $targetChemFormId = $this->chemFormRepo->getChemFormId($concreteMolecule->getMoleculeKey());

        $this->renderMolecule($concreteMolecule);
        $moleculePage = Title::newFromId($molecule_page_id);
        $paramsJob = [
            'molOrRxn' => base64_encode($concreteMolecule->getMolOrRxn()),
            'moleculeKey' => $concreteMolecule->getMoleculeKey(),
            'smiles' => $concreteMolecule->getSmiles(),
            'inchi' => $concreteMolecule->getInchi(),
            'inchikey' => $concreteMolecule->getInchiKey(),
        ];
        $job = new MoleculePageUpdateJob($moleculePage, $paramsJob);
        $job->run();


        $chemFormId = $moleculePage->getText();
        $moleculeKey = $this->chemFormRepo->getMoleculeKey($chemFormId);
        $paramsJob = [];
        $paramsJob['targetChemForm'] = $concreteMolecule;
        $paramsJob['targetChemFormId'] = $targetChemFormId;
        $paramsJob['oldChemFormId'] = $chemFormId;
        $paramsJob['oldMoleculeKey'] = $moleculeKey;
        $paramsJob['replaceChemFormId'] = !is_null($targetChemFormId);
        $job = new AdjustMoleculeReferencesJob($moleculePage, $paramsJob);
        $job->run();


        $moleculeCollection = $collection['chemForm'];
        $moleculeCollectionTitle = $collection['title'];

        $concreteMoleculeTitle = $moleculePage;
        $this->logger->log("Updated molecule/reaction page: {$concreteMoleculeTitle->getPrefixedText()}, "
            . "chemform: {$concreteMolecule->__toString()}, moleculeKey: {$concreteMolecule->getMoleculeKey()}");

        $moleculeCollectionId = $this->chemFormRepo->getChemFormId($moleculeCollection->getMoleculeKey());
        $this->chemFormRepo->addConcreteMolecule($this->publicationPageTitle, $moleculeCollectionTitle,
            $concreteMoleculeTitle, $moleculeCollectionId, $rGroups);
    }

}