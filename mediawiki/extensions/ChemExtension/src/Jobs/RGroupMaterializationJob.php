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
use MediaWiki\MediaWikiServices;

class RGroupMaterializationJob extends Job
{

    private $logger;
    private $rGroupClient;
    private $publicationPageTitle;
    private $pageCreator;
    private $moleculeRendererClient;
    private $chemFormRepo;

    public function __construct($title, $params)
    {
        parent::__construct('RGroupMaterializationJob', $title, $params);

        global $wgCEUseMoleculeRGroupsClientMock;
        $this->rGroupClient = $wgCEUseMoleculeRGroupsClientMock ? new MoleculeRGroupServiceClientMock()
            : new MoleculeRGroupServiceClientImpl();
        $this->publicationPageTitle = $title;
        $this->pageCreator = new MoleculePageCreator();
        $this->moleculeRendererClient = new MoleculeRendererClientImpl();
        $this->logger = new LoggerUtils('MoleculesImportJob', 'ChemExtension');
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_PRIMARY);
        $this->chemFormRepo = new ChemFormRepository($dbr);
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
                $this->createMoleculePage($concreteMolecule, $collection, $rGroups);
                $this->renderMolecule($concreteMolecule);
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

}