<?php

namespace DIQA\InvestigationImport\Importer;

use PhpOffice\PhpSpreadsheet\IOFactory;
use \PhpOffice\PhpSpreadsheet\Reader\Csv;
use ZipArchive;
use Exception;

class ImportFileReader
{


    public function open_zip_extr_data($zip_file)
    {
        ## opens zip file and returns an array of each experiment in the zipfile
        $output = [];
        $temp_folder_location = sys_get_temp_dir() . "/" . basename($zip_file) . "_" . uniqid();
        mkdir($temp_folder_location);
        $file_array = $this->zip_file_parsing($zip_file, $temp_folder_location);
        $cv_data = ($this->xml_parsing($temp_folder_location . "/" . $file_array["1"]));
        $wikitext = $this->update_data($cv_data);
        $intial_start_int = 1;
        $jdx_or_csv = "csv";
        if (sizeof($file_array["csv"]) == 0){
            $jdx_or_csv="jdx";
        }
        foreach ($file_array[$jdx_or_csv] as $peak_file) {
            if ($intial_start_int == 1){
            }
            else {
                $wikitext = str_replace("{{Cyclic Voltammetry experiments\n|experiments=","",$wikitext);
            }
            $peak_string = $this->csv_parsing($temp_folder_location . "/" . $peak_file);
            var_dump($peak_string);
            $peak_list = explode(",", $peak_string);
            $new_peak_string = "$peak_list[0],$peak_list[2];";
            $val = $new_peak_string;
            $key = "redox potential";
            $new_data = "|$key=$val\n";
            if ($intial_start_int == count($file_array[$jdx_or_csv]))
                $wikitext_data = $wikitext . $new_data . "}}\n}}";
            else{
                $wikitext_data = $wikitext . $new_data . "}}";
            }
            $intial_start_int = $intial_start_int + 1;
            echo($intial_start_int);
            $output[] = $wikitext_data;

        }
        FileUtil::rrmdir($temp_folder_location);
        return $output;
    }

    private function zip_file_parsing($zip_file, $temp_folder_location)
    {
        $zip = new ZipArchive;
        $result = $zip->open($zip_file);
        if ($result !== true) {
            throw new Exception("Cannot open zipfile: $zip_file");
        }
        $result = $zip->extractTo($temp_folder_location);
        if ($result !== true) {
            throw new Exception("Cannot extract from zipfile: $zip_file");
        }
        $zip->close();

        $file_array = scandir($temp_folder_location);
        $output = [];
        $jdx_array = [];
        $csv_array = [];
        foreach ($file_array as $file) {
            if (preg_match("/.xlsx/", $file)) {
                $output["1"] = $file;
            }
            if (preg_match("/.bagit.edit.jdx/", $file)) {
                $jdx_array[] = $file;
            }
            if (preg_match("/.bagit.edit.csv/", $file)) {
                $csv_array[] = $file;
            }
        }
        $output["jdx"] = $jdx_array;
        $output["csv"] = $csv_array;
        return $output;
    }
    public function csv_parsing($csv_file)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        $spreadsheet = $reader->load($csv_file);
        $reader->setSheetIndex(0);
        $dataArray1 = $spreadsheet->getActiveSheet()->rangeToArray('A9:G9');
        $dataArray2 = $spreadsheet->getActiveSheet()->rangeToArray('A10:G10');
        $count = 0;
        $output = [];
        foreach ($dataArray1[0] as $data_value){
            if (is_numeric($dataArray2[0][$count]) ){
            $output[$data_value] = $this->round_cleanly($dataArray2[0][$count]);
            }
            else{
            $output[$data_value] = ($dataArray2[0][$count]);  
            }
            $count = $count + 1;
        }
        $output = array_values($output);

        $output = implode(",", $output);
        return ($output);
    }

    public function jdx_parsing($jdx_file)
    {
        $output=[];
        $term_array = ["##MAXX","##MAXY","##MINX","##MINY"];
        $peaktable_array =["start"=>"$$ === CHEMSPECTRA CYCLIC VOLTAMMETRY ===","data"=>"##\$CSCYCLICVOLTAMMETRYDATA="
            ,"end"=>"##END="];
        $jdx_file_contents =fopen($jdx_file,"r");
        $file_text =fread($jdx_file_contents, filesize($jdx_file));
        fclose($jdx_file_contents);
        $text_array = explode("\n",$file_text);
        $count= 0;
        $boo =false;
        $data_index = [];
        foreach($text_array as $text_line){
            if ($text_line === $peaktable_array["start"]){
              $boo =true;
            }
            if ($text_line === $peaktable_array["data"] and $boo == true){
              $data_index[] = $count;
            }
            if ($text_line === $peaktable_array["end"] and $boo == true){
              $data_index[] = $count;
              $boo = false;
            }
            $count = $count + 1;
        }
        $data_index[0]= $data_index[0] + 1;
        $data_index[1]= $data_index[1] - 1;
        $peak_string  ="";
        for($x= $data_index[0];$x <= $data_index[1]; $x++){    
            $single_peak_array =[];        
            $text_array[$x] =trim($text_array[$x],"()");
            $peak_array = explode(", ",$text_array[$x]);
            // var_dump($peak_array);
            foreach ($peak_array as $data_point){
            $small_data = $this->round_cleanly($data_point,2);
            $single_peak_array[] = $small_data;    
            }
            $single_peak = implode(", ",$single_peak_array);
          $peak_string = $peak_string . $single_peak . ";"; 
        }    
        

        $output = $peak_string;
        return $output;
    }

    function round_cleanly($number_in)
    {
        if (str_contains($number_in,"e") ){
            $e_value = "e" . explode("E",$number_in)[1];
            $number = explode("e",$number_in)[0];
        }
        if (str_contains($number_in,"E") ){
            $e_value = "e" . explode("E",$number_in)[1];
            $number = explode("e",$number_in)[0];
        }
        else{
            $e_value = "";
            $number = $number_in;
        }
        $number = round($number,2);
        return($number . $e_value);
    }

    private function xml_parsing($file)
    {
        $reader = IOFactory::createReader("Xlsx");
        $reader->setLoadSheetsOnly('cyclic voltammetry (CV)');
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
        for ($x = 4; $x <= 91; $x++) {
            if (($dataArrayE[$x]["E"]) != "") ;
            $dataArrayC[$x]["E"] = $dataArrayE[$x]["E"];
            ($CV_metadata[$dataArrayC[$x]["E"]] = $dataArrayC[$x]["C"]);
        }
        $regex = "/........-....-....-....-............/";
        foreach ($CV_metadata as $key => $value) {
            if (preg_match($regex, $key) || $key == " ") {
                unset($CV_metadata[$key]);
            }
        }
        // need to add code to change molecule common name to Molecule:Id
        return $CV_metadata;
    }

    private function update_data($extracted_data)
    {
        $mediawiki_catagories = ["id" => "anl", "anl conc" => "test", "redox potential" => "test", "solvent" => "solv", "amount_sol" => "solv vol", "salt" => "electrolyte", "concentration_salt" => "el conc", "reference" => "int ref comp", "scan_rate" => "scan rate", "step_size" => "scan number", "potential window" => "potential window", "scan dir" => "scan dir", "atmosphere" => "gas", "temperature" => "temp", "conditions" => "cond", "working" => "WE", "working_area" => "WE area", "counter" => "CE", "reference" => "RE", "include" => "include"];
        $wikitext_data = "";
        $table_start = "{{Cyclic Voltammetry experiments\n|experiments={{Cyclic Voltammetry\n";
        $mediawiki_data = [];
        $volt_array = [];

        foreach ($extracted_data as $key => $val) {
            if (preg_match("/voltage/", $key)) {
                if (preg_match("/\d,\d{5}E.\d{3}/", $val)) {
                    $val = (float)$val;
                }
                $volt_array[] = $val;
            }
        }
        $mediawiki_data["redox 1 potential"] = $volt_array;
        foreach ($mediawiki_catagories as $mw_key => $mw_val) {
            if (array_key_exists($mw_key, $extracted_data)) {
                // echo($extracted_data[$mw_key]. "\n");
                $mediawiki_data[$mw_val] = $extracted_data[$mw_key];
            }
        }
        foreach ($mediawiki_data as $key => $val) {
            if (is_array($val)) {
                $new_string = "$val[0],$val[1];$val[2],$val[3]";
                // var_dump($new_string);
                $val = $new_string;
            }
            // var_dump($val);
            if(is_numeric($val)){
                $val = $this->round_cleanly($val);
            }
            $new_data = "|$key=$val\n";
            $wikitext_data = $wikitext_data . $new_data;
        }
        $wikitext_data = $table_start . $wikitext_data;
        // var_dump($wikitext_data);
        return ($wikitext_data);
    }
}
