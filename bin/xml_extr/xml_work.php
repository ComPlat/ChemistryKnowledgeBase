<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load("CV_generic_dataset_withExValues.xlsx");
$dataArrayE = $spreadsheet->getActiveSheet()
->rangeToArray(
    'E4:E91',     // The worksheet range that we want to retrieve
    NULL,        // Value that should be returned for empty cells
    TRUE,        // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
    TRUE,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
    TRUE         // Should the array be indexed by cell row and cell column
);
$dataArrayC = $spreadsheet->getActiveSheet()
->rangeToArray(
    'C4:C91',     // The worksheet range that we want to retrieve
    NULL,        // Value that should be returned for empty cells
    TRUE,        // Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
    TRUE,        // Should values be formatted (the equivalent of getFormattedValue() for each cell)
    TRUE         // Should the array be indexed by cell row and cell column
);
$CV_metadata = array();
// var_dump($dataArrayC[4]["C"])


for ($x = 4; $x <= 91; $x++){  
    if (($dataArrayE[$x]["E"])!= ""); 
 $dataArrayC[$x]["E"] = $dataArrayE[$x]["E"];

($CV_metadata[$dataArrayC[$x]["E"]] = $dataArrayC[$x]["C"]);}
print_r($CV_metadata);
// var_dump($CV_metadata)
?>
