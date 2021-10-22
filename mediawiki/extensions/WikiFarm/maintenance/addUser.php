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
 * adds a user to wikifarm table
 */
class addUser extends \Maintenance
{

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('adds a user to the WikiFarm storage backend.');
        $this->addOption('user', 'User name', true, true);
        $this->addOption('wiki', 'Wiki Id', true, true);
        $this->addOption('status', 'Status (USER)', false, true);
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


        $db = $this->getConnection();
        $username = $this->getOption("user");
        $wikiId = $this->getOption("wiki");
        $status_enum = $this->getOption("status");

        $userId = \User::idFromName($username);
        if (is_null($userId)) {
            print "\nUser '$username' does not exist\n";
            exit;
        }
        $db->insert('wiki_farm_user',
            ['fk_user_id' => $userId,
             'fk_wiki_id' => $wikiId,
             'status_enum' => $status_enum
            ]);

    }


    private function reportMessage($message)
    {
        $this->output($message);
    }

}

$maintClass = addUser::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
