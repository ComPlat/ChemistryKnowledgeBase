<?php

namespace DIQA\WikiFarm\Maintenance;

use DIQA\WikiFarm\Setup;

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

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Sets up the WikiFarm storage backend.');
        $this->addOption('delete', 'Delete all WikiFarm tables, uninstall the selected storage backend.');
    }

    public static function onLoadExtensionSchemaUpdates( \DatabaseUpdater $updater ) {
        print ("=== Wiki Farm =============================================================");
        $db = $updater->getDB();
        self::setupTables($db);
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

        if (!Setup::isEnabled()) {
            $this->reportMessage("\nYou need to have WikiFarm enabled in order to run the maintenance script!\n");
            exit;
        }
        $this->reportMessage("\nInstalling WikiFarm tables...");

        $db = $this->getConnection();
        if ($this->hasOption('delete')) {
            $this->dropStore();
        } else {
            self::setupTables($db);
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

    private static function setupTables($db)
    {
        $db->query('CREATE TABLE IF NOT EXISTS wiki_farm_user (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        fk_user_id INT(10) UNSIGNED NOT NULL,
                        wiki_id INT(10) NOT NULL,
                        status_enum VARCHAR(10) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (fk_user_id) 
                        REFERENCES `user`(user_id)
                        ON UPDATE RESTRICT 
                        ON DELETE CASCADE
                    )  ENGINE=INNODB;');

    }

}

$maintClass = setupStore::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
