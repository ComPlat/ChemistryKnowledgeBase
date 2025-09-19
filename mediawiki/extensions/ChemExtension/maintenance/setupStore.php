<?php

namespace DIQA\ChemExtension\Maintenance;


use DIQA\ChemExtension\CategoryIndexRepository;
use DIQA\ChemExtension\Literature\LiteratureRepository;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\PubChem\PubChemRepository;
use DIQA\ChemExtension\Utils\ArrayTools;
use MediaWiki\Installer\DatabaseUpdater;
use Wikimedia\Rdbms\IMaintainableDatabase;


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

    public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
        print ("=== ChemExtension =============================================================");
        $db = $updater->getDB();
        self::setupTables($db);
        print ("\n");
    }

    /**
     * @param \Wikimedia\Rdbms\IMaintainableDatabase $db
     */
    public static function setupTables(IMaintainableDatabase $db): void
    {
        $tables = [
            (new ChemFormRepository($db))->setupTables(),
            (new PubChemRepository($db))->setupTables(),
            (new LiteratureRepository($db))->setupTables(),
            (new CategoryIndexRepository($db))->setupTables()
        ];
        $tables = ArrayTools::flatten($tables);
        foreach ($tables as $t) {
            print ("\nCreated/updated table $t.");

        }
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
        $tables = [
            (new ChemFormRepository($db))->dropTables(),
            (new PubChemRepository($db))->dropTables(),
            (new LiteratureRepository($db))->dropTables()
        ];
        $tables = ArrayTools::flatten($tables);
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
