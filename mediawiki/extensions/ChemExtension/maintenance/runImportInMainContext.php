<?php

namespace DIQA\ChemExtension\Maintenance;


use DIQA\ChemExtension\Utils\WikiTools;
use DIQA\ChemExtension\WikiRepository;
use Title;

/**
 * Load the required class
 */
if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

class runImportInMainContext extends \Maintenance
{

    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Import publication pages into the main wiki');
        $this->addOption('file', 'file with import data', true, true);
        $this->addOption('title', 'page title', true, true);
    }


    public function getDbType()
    {
        return \Maintenance::DB_ADMIN;
    }

    /**
     * @see Maintenance::execute
     *
     * @since 2.0
     */
    public function execute()
    {
        $file = $this->getOption('file');
        $titleText = $this->getOption('title');

        print "\nImport '$file' into '$titleText'";
        $content = file_get_contents($file);
        $o = json_decode($content);
        $title = Title::newFromText($titleText);

        WikiTools::doEditContent($this->getOption('title'), $o->wikiText, "auto-generated", $title->exists() ? EDIT_UPDATE : EDIT_NEW);
    }

}

$maintClass = runImportInMainContext::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
