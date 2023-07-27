<?php
namespace DIQA\InvestigationImport\Maintenance;
use DIQA\InvestigationImport\Importer\ImportFileReader;
use DIQA\ChemExtension\Utils\WikiTools;
use Maintenance;
/**
 * Load the required class
 */
// include_path ="mediawiki\extensions\InvestigationImport\src\Importer\ImportFileReader.php";

include_once './../src/Importer/ImportFileReader.php';
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
        $this->addOption('f', 'file to import', true, true);
    }
    public function getDbType()
    {
        return Maintenance::DB_ADMIN;
    }
    public function execute()
    {
        $file = $this->getOption('f');
        $reader = new ImportFileReader($file);
        $cv_data = ($reader->xml_parsing($file));
        $wikitext = $reader->update_data($cv_data);
        WikiTools::doEditContent("test_title", $wikitext, "comment",EDIT_NEW);
    }
}
$maintClass = import::class;
require_once(RUN_MAINTENANCE_IF_MAIN);

