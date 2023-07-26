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
            echo("key removed $key\n");
        }
      }
      // need to add code to change molecule common name to Molecule:Id
      return $CV_metadata;
    }
    public function update_data($extracted_data){
      $wikitext_data = "";
      $table_start = "{{ class=\"wikitable\" style=\"margin:auto\"\n|+ Captiontext\n";
        foreach ($extracted_data as $key => $val){
          $new_data = "|$key=$val\n";
          $wikitext_data = $wikitext_data . $new_data;
          }
        $wikitext_data = $table_start . $wikitext_data . "}}\n";
        return ($wikitext_data);
      }
}
