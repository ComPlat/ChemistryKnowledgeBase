<?php

namespace DIQA\ChemExtension\Maintenance;


use DIQA\ChemExtension\ChemScanner\ChemScannerRequest;
use DIQA\ChemExtension\WikiRepository;
use ExtensionRegistry;
use Exception;

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
class uploadToChemscanner extends \Maintenance
{

    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Uploads a file to Chemscanner');
        $this->addOption("file", 'File path of file to upload');

    }


    public function getDbType()
    {
        return \Maintenance::DB_ADMIN;
    }

    private function getConnection()
    {
        return $this->getDB(DB_PRIMARY);
    }

    /**
     * @see Maintenance::execute
     *
     * @since 2.0
     */
    public function execute()
    {
        print "\nRequest Chemscanner...";
        try {

            $chreq = new ChemScannerRequest($this->getOption('file'));
            $createPages = $chreq->send();
            foreach ($createPages as $page) {
                print "\n\tCreated page for result: $page";
            }
            print "\nDONE.\n";
        } catch (Exception $e) {
            $msg = $e->getMessage();
            print "\n$msg\n";
        }
    }

}

$maintClass = uploadToChemscanner::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
