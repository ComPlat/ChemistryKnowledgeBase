<?php

namespace DIQA\InvestigationImport\Importer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// require 'vendor/autoload.php';
class ImportFileReader {
  public function open_zip_extr_data($zip_file){
    ## opens zip file and returns an array of each experiment in the zipfile
    $output =[];
    $file_array = $this->zip_file_parsing($zip_file);
    $cv_data = ($this->xml_parsing($file_array[1]));
    $wikitext = $this->update_data($cv_data);
    # echo($wikitext);
    foreach ($file_array[2] as $peak_file){
      $xy =$this->jdx_parsing($peak_file);
      $new_string = $xy['MAXX'] .",". $xy["MAXY"].";".$xy["MINX"].",".$xy["MINY"];
        // var_dump($new_string);
      $val = $new_string;
      $key = "redox potential";
        // var_dump($val);  
      $new_data = "|$key=$val\n";
      $wikitext_data = $wikitext_data . $new_data;
      $output[] = $wikitext_data;
    }
    return $output;
  }
    
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
      } 
      else {
        echo "File is not a CV file.";
        $uploadOk = 0;
      } 
    }
  }
  public function zip_file_parsing($zip_file){
    $zip = new ZipArchive;
    $zip->open($zip_file);
    $zip->extractTo('../resources/tmp_file');
    $zip->close();
    $file_array = scandir("../resources/tmp_file");
    $output =[];
    $jdx_array =[];
    foreach ($file_array as $file){
      if (preg_match(".xlsx",$file)){
              $output[1] =$file ;
            }
      if (preg_match(".peak.bagit.jdx",$file)){
        $jdx_array[]= $file;
      }
    }
    $output[2] = $jdx_array;
    return $output;
  }
  public function jdx_parsing($jdx_file){
    $output=[];
    $term_array = ["##MAXX","##MAXY","##MINX","##MINY"];
    $file_text =fread($jdx_file);
    $text_array = explode("\n",$file_text);
    foreach($text_array as $text_line){
      foreach ($term_array as $term){
        if (preg_match($term)){
          str_replace("##","",$term);
          $split_str = explode("=",$text_line);
          $output[$term] = $split_str[1] ;
        }
      }
    }
    return $output;
  }
  
  public function xml_parsing($file) {
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
    $reader->setLoadSheetsOnly('cyclic voltammetry (CV)');
    // $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
    $spreadsheet = $reader->load($file);
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
    $mediawiki_catagories = ["id"=>"anl","anl conc"=>"test","redox potential"=>"test","solvent"=>"solv","amount_sol"=>"solv vol","salt"=>"electrolyte","concentration_salt"=>"el conc","reference"=>"int ref comp","scan_rate"=>"scan rate","step_size"=>"scan number","potential window"=>"potential window","scan dir"=>"scan dir","atmosphere"=>"gas","temperature"=>"temp","conditions"=>"cond","working"=>"WE","working_area"=>"WE area","counter"=>"CE" ,"reference"=>"RE" ,"include"=>"include"];
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
    $mediawiki_data["redox 1 potential"]= $volt_array;
    foreach ($mediawiki_catagories as $mw_key => $mw_val){
      if (array_key_exists($mw_key,$extracted_data)){
        // echo($extracted_data[$mw_key]. "\n");
        $mediawiki_data[$mw_val] = $extracted_data[$mw_key];
      }
    }
    foreach ($mediawiki_data as $key => $val){
      if (is_array($val)){
        $new_string = "$val[0],$val[1];$val[2],$val[3]";
        // var_dump($new_string);
        $val = $new_string;
        }
        // var_dump($val);  
      
      $new_data = "|$key=$val\n";
      $wikitext_data = $wikitext_data . $new_data;
      }
    $wikitext_data = $table_start . $wikitext_data . "}}\n}}";
    var_dump($wikitext_data);
    return ($wikitext_data);
  }
}
