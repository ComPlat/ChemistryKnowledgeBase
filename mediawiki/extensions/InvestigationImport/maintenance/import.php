<?php


namespace DIQA\InvestigationImport\Maintenance;


use DIQA\InvestigationImport\Importer\ImportFileReader;
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
        $reader->xml_parsing($file);
    }
    // public function call_xml_parsing($file_location){
    //     $var_1 =new ImportFileReader();
    //     $output = $var_1->xml_parsing($file_location);
    //     echo get_class($output);
    //     }
    // public function execute(){
    //     echo get_class($this);
    //     include_once './../src/Importer/ImportFileReader.php';
    //     // echo(get_class_methods('ImportFileReader'));
    //     // // xml_parsing("../xml_extr/CV_generic_dataset_withExValues.xlsx");
    //     $this->call_xml_parsing("../xml_extr/CV_generic_dataset_withExValues.xlsx");
    //     print "\nDo something...";
    //     print "\nfinished.";
    //     print "\n";
}



$maintClass = import::class;
require_once(RUN_MAINTENANCE_IF_MAIN);

