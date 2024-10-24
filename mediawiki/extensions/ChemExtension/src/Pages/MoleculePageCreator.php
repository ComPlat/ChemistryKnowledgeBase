<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;
use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use Title;

class MoleculePageCreator
{

    private $logger;

    public function __construct()
    {
        $this->logger = new LoggerUtils('MoleculePageCreator', 'ChemExtension');
    }

    /**
     * @throws Exception
     */
    public function createNewMoleculePage(ChemForm $chemForm, ?Title $parent = null, $async = false): array
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_MASTER
        );

        $chemFormRepository = new ChemFormRepository($dbr);
        $moleculeKey = $chemForm->getMoleculeKey();
        $reservedKey = $chemFormRepository->getChemFormId("reserved-" . $moleculeKey);
        if (!is_null($reservedKey)) {
            $chemFormRepository->commitReservedMolecule($moleculeKey);
        }
        $id = $chemFormRepository->addChemForm($moleculeKey);

        $title = MoleculePageCreationJob::getPageTitleToCreate($id, $chemForm->getMolOrRxn());


        $jobParams = [];
        $jobParams['chemForm'] = $chemForm;
        $jobParams['parent'] = $parent;
        $job = new MoleculePageCreationJob($title, $jobParams);
        if ($async) {
            JobQueueGroup::singleton()->push($job);
        } else {
            $job->run();
        }


        return ['title' => $title, 'chemformId' => $id];
    }


}