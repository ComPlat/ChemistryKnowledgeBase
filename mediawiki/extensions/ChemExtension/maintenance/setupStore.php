<?php

namespace DIQA\ChemExtension\Maintenance;


use DIQA\ChemExtension\Pages\ChemFormRepository;

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
        $this->addDescription('Sets up the ChemExtension storage backend.');
        $this->addOption('delete', 'Delete all ChemExtension tables, uninstall the selected storage backend.');

    }

    public static function onLoadExtensionSchemaUpdates( \DatabaseUpdater $updater ) {
        print ("=== ChemExtension =============================================================");
        $db = $updater->getDB();
        self::setupTables($db);
        print ("\n");
    }

    /**
     * @param \Wikimedia\Rdbms\IMaintainableDatabase $db
     */
    private static function setupTables(\Wikimedia\Rdbms\IMaintainableDatabase $db): void
    {
        $tables = (new ChemFormRepository($db))->setupTables();
        foreach ($tables as $t) {
            print ("\nCreated table $t.");

        }
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
        $this->repository = new ChemFormRepository($this->getConnection());

        $this->reportMessage("\nInstalling ChemExtension tables...");

        $db = $this->getConnection();
        if ($this->hasOption('delete')) {
            $this->dropStore();
        } else {
            self::setupTables($db);
        }
        $this->reportMessage("\n...done.\n");
    }

    private function dropStore()
    {
        $db = $this->getConnection();
        $tables = (new ChemFormRepository($db))->dropTables();
        foreach ($tables as $t) {
            print ("\nRemoved table $t.");

        }
    }

    private function reportMessage($message)
    {
        $this->output($message);
    }

}

$maintClass = setupStore::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
