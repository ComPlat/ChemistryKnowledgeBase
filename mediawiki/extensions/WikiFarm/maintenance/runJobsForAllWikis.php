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
 * adds a user to wikifarm table
 */
class runJobsForAllWikis extends \Maintenance
{

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('runs jobs in all wikis');

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
        global $IP;
        if (!Setup::isEnabled()) {
            $this->output("\nYou need to have WikiFarm enabled in order to run the maintenance script!\n");
            exit;
        }
        $repository = new WikiRepository($this->getConnection());

        $allWikis = $repository->getAllWikis();

        global $wgWikiFarmBinFolder;
        $wgWikiFarmBinFolder = $wgWikiFarmBinFolder ?? "$IP/../bin";
        foreach($allWikis as $wiki) {
            echo "\nRun jobs for wiki $wiki";
            echo shell_exec("$wgWikiFarmBinFolder/runJobForWiki.sh wiki$wiki");
        }
        echo "\nRun jobs for main wiki";
        echo shell_exec("$wgWikiFarmBinFolder/runJobForWiki.sh main");
        echo "\n";
    }

}

$maintClass = runJobsForAllWikis::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
