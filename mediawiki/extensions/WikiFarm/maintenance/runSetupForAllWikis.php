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
class runSetupForAllWikis extends \Maintenance
{

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('runs setup in all wikis');

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
        chdir($wgWikiFarmBinFolder);
        foreach($allWikis as $wiki) {
            $id = $wiki['id'];
            echo shell_exec("bash $wgWikiFarmBinFolder/runSetupForWiki.sh wiki$id 2>&1");
        }

        echo shell_exec("bash $wgWikiFarmBinFolder/runSetupForWiki.sh main 2>&1");
        echo "\n";
    }

}

$maintClass = runSetupForAllWikis::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
