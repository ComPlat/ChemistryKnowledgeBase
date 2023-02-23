<?php

namespace DIQA\ChemExtension\Maintenance;


use DIQA\ChemExtension\ChemScanner\ChemScannerRequest;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\PubChem\PubChemRecordResult;
use DIQA\ChemExtension\PubChem\PubChemRepository;
use DIQA\ChemExtension\PubChem\PubChemClient;
use DIQA\ChemExtension\WikiRepository;
use ExtensionRegistry;
use Exception;
use MediaWiki\MediaWikiServices;

/**
 * Load the required class
 */
if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Uploads a document to chemscanner
 */
class cleanUpChemTables extends \Maintenance
{

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Cleans up the chemwiki tables');
        $this->addOption('dry-run', 'Shows what would happen but does not really delete anything', false, false);
    }


    public function getDbType()
    {
        return \Maintenance::DB_ADMIN;
    }

    /**
     * @see Maintenance::execute
     *
     * @since 2.0
     */
    public function execute()
    {
        try {
            print "\nCleaning tables...";
            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
            $repo = new ChemFormRepository($dbr);
            $ids = $repo->getUnusedMoleculeIds();
            $i = 1;
            foreach($ids as $id) {
                $moleculeTitle = \Title::newFromText($id, NS_MOLECULE);
                $reactionTitle = \Title::newFromText($id, NS_REACTION);
                if (!$moleculeTitle->exists() && !$reactionTitle->exists()) {
                    print "\n\tRemove chemform ($i): $id}";
                    if (!$this->hasOption('dry-run')) {
                        $repo->deleteAllChemFormIndexByPageId($id);
                        $repo->deleteChemForm($id);
                    }
                }

                $i++;
            }
            print "\nfinished\n";
        } catch (Exception $e) {
            $msg = $e->getMessage();
            print "\n$msg\n";
        }
    }

}

$maintClass = cleanUpChemTables::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
