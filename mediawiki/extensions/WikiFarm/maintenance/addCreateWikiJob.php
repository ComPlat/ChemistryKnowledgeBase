<?php

namespace DIQA\WikiFarm\Maintenance;

use DIQA\WikiFarm\CreateWikiJob;
use DIQA\WikiFarm\Setup;
use DIQA\WikiFarm\WikiRepository;
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\LoadBalancer;

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
class addCreateWikiJob extends \Maintenance
{

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('adds a job which creates a new wiki');
        $this->addOption('name', 'Wiki name', true, true);
        $this->addOption('user', 'User who creates', true, true);
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
        $name = $this->getOption('name');
        $user = $this->getOption('user');
        (new WikiRepository($this->getConnection()))->createWikiJob($name, $user);
    }


    private function reportMessage($message)
    {
        $this->output($message);
    }

}

$maintClass = addCreateWikiJob::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
