<?php

namespace DIQA\InvestigationImport\Importer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// require 'vendor/autoload.php';
class ImportFileReader {
    
  function upload(){
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $FileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    // Check if file is a actual xlxs or other file type
    if(isset($_POST["submit"])) {
      if($FileType == "xlxs") {
        echo "File is an CV file - " . $check["mime"] . ".";
        $uploadOk = 1;
      } else {
        echo "File is not a CV file.";
        $uploadOk = 0;
      }
    }
        }
   
  public function xml_parsing($file) {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
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
      for ($x = 4; $x <= 91; $x++){  
          if (($dataArrayE[$x]["E"])!= ""); 
              $dataArrayC[$x]["E"] = $dataArrayE[$x]["E"];
              ($CV_metadata[$dataArrayC[$x]["E"]] = $dataArrayC[$x]["C"]);}
      $regex = "/........-....-....-....-............/";
      foreach ($CV_metadata as $key => $value){
        if (preg_match($regex, $key ) || $key== " ")  {
            unset($CV_metadata[$key]);
            // echo("key removed $key\n");
        }
      }
      // need to add code to change molecule common name to Molecule:Id
      return $CV_metadata;
    }
  public function update_data($extracted_data){
    $mediawiki_catagories = ["sample"=>"anl","anl conc"=>"test","redox potential"=>"test","solvent"=>"solv","amount_sol"=>"solv vol","salt"=>"electrolyte","concentration_salt"=>"el conc","reference"=>"int ref comp","scan_rate"=>"scan rate","step_size"=>"scan number","potential window"=>"potential window","scan dir"=>"scan dir","atmosphere"=>"gas","temperature"=>"temp","conditions"=>"cond","working"=>"WE","working_area"=>"WE area","counter"=>"CE" ,"reference"=>"RE" ,"include"=>"include"];
    $wikitext_data = "";
    $table_start = "{{Cyclic Voltammetry experiments\n|experiments={{Cyclic Voltammetry\n";
    $mediawiki_data =[];
    $volt_array =[];
    foreach ($extracted_data as $key => $val){
      if (preg_match("/voltage/",$key)){
        if (preg_match("/\d,\d{5}E.\d{3}/",$val)){
          $val = (float)$val;
        }
        $volt_array[]= $val;
      }
    }
    $mediawiki_data["redox_potential"]= $volt_array;
    foreach ($mediawiki_catagories as $mw_key => $mw_val){
      if (array_key_exists($mw_key,$extracted_data)){
        // echo($extracted_data[$mw_key]. "\n");
        $mediawiki_data[$mw_val] = $extracted_data[$mw_key];
      }
    }
    foreach ($mediawiki_data as $key => $val){
      if (is_array($val)){
        $val = implode(",",$val);
      } 
      $new_data = "|$key=$val\n";
      $wikitext_data = $wikitext_data . $new_data;
    }
    $wikitext_data = $table_start . $wikitext_data . "}}\n}}";
    return ($wikitext_data);
  }
}
