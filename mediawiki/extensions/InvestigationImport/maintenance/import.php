<?php

namespace DIQA\InvestigationImport\Maintenance;


use DIQA\InvestigationImport\Importer\ImportFileReader;
use Maintenance;

/**
 * Load the required class
 */
if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

class import extends \Maintenance
{

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Imports investigations');
        $this->addOption('f', 'file to import', true, true);
    }

    public function getDbType()
    {
        return Maintenance::DB_ADMIN;
    }

    public function execute()
    {
        $file = $this->getOption('f');
        $reader = new ImportFileReader($file);
        $reader->xml_parsing();

    }

}

$maintClass = import::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
