<?php

namespace DIQA\WikiFarm\Maintenance;

use DIQA\WikiFarm\Setup;
use DIQA\WikiFarm\WikiRepository;

/**
 * Load the required class
 */
if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Sets up the storage backend
 */
class setupStore extends \Maintenance
{

    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Sets up the WikiFarm storage backend.');
        $this->addOption('delete', 'Delete all WikiFarm tables, uninstall the selected storage backend.');

    }

    public static function onLoadExtensionSchemaUpdates( \DatabaseUpdater $updater ) {
        print ("=== Wiki Farm =============================================================");
        $db = $updater->getDB();
        (new WikiRepository($db))->setupTables();
        print ("\nCreated table wiki_farm.");
        print ("\nCreated table wiki_farm_user.");
        print ("\n");
    }

    public function getDbType()
    {
        return \Maintenance::DB_ADMIN;
    }

    private function getConnection()
    {
        return $this->getDB(DB_MASTER);
    }

    /**
     * @see Maintenance::execute
     *
     * @since 2.0
     */
    public function execute()
    {
        $this->repository = new WikiRepository($this->getConnection());
        if (!Setup::isEnabled()) {
            $this->reportMessage("\nYou need to have WikiFarm enabled in order to run the maintenance script!\n");
            exit;
        }
        $this->reportMessage("\nInstalling WikiFarm tables...");

        $db = $this->getConnection();
        if ($this->hasOption('delete')) {
            $this->dropStore();
        } else {
            $this->repository->setupTables();
            $this->reportMessage("\nCreated table wiki_farm.");
            $this->reportMessage("\nCreated table wiki_farm_user.");
        }
        $this->reportMessage("\n...done.\n");
    }

    private function dropStore()
    {
        $db = $this->getConnection();
        $db->query('DROP TABLE IF EXISTS wiki_farm_user;');
        $this->reportMessage("\nDeleted table wiki_farm_user.");
    }

    private function reportMessage($message)
    {
        $this->output($message);
    }

}

$maintClass = setupStore::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
