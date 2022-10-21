<?php

namespace DIQA\ChemExtension\Maintenance;


use DIQA\ChemExtension\ChemScanner\ChemScannerRequest;
use DIQA\ChemExtension\PubChem\PubChemRecordResult;
use DIQA\ChemExtension\PubChem\PubChemRepository;
use DIQA\ChemExtension\PubChem\PubChemService;
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
class pubChem extends \Maintenance
{

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Requests PubChem for data about a molecule and stores it in the DB');
        $this->addOption('inchikey', 'InchiKey of the substance', true, true);
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
        print "\nRequest PubChem...";
        try {
            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_MASTER);
            $repo = new PubChemRepository($dbr);
            $result = $repo->getPubChemResult($this->getOption('inchikey'));
            $record = $result['record'];
            print "\n CID: " . $record->getCID();
            print "\n IUPAC Name: " . $record->getIUPACName();
            print "\n Molecular mass: " . $record->getMolecularMass();
            print "\n Molecular formula: " . $record->getMolecularFormula();
            print "\n logP: " . $record->getLogP();

            $synonyms = $result['synonyms'];
            print "\n Synonyms: " . implode(',', $synonyms->getSynonyms());
            print "\n CAS: " . $synonyms->getCAS();

            $categories = $result['categories'];
            print "\n Vendors: " . ($categories->hasVendors() ? "ja": "nein");
            print "\nDONE.\n";
        } catch (Exception $e) {
            $msg = $e->getMessage();
            print "\n$msg\n";
        }
    }

}

$maintClass = pubChem::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
