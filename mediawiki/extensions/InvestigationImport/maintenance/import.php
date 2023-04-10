<?php

namespace DIQA\InvestigationImport\Maintenance;


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
    }

    public function getDbType()
    {
        return Maintenance::DB_ADMIN;
    }

    public function execute()
    {
        print "\nDo something...";
        print "\nfinished.";
        print "\n";
    }

}

$maintClass = import::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
