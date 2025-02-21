<?php

namespace DIQA\ChemExtension\Indigo;

use DIQA\ChemExtension\Utils\GeneralTools;
use DIQA\ChemExtension\Utils\MolfileProcessor;
use Exception;

class IndigoTool {

    public static function convertSmilesToMolfile(string $smiles) : string {
        global $IP;
        $binFolder = "$IP/extensions/ChemExtension/resources/indigo";
        exec("java -jar $binFolder/convertSmilesToMolfile.jar \"$smiles\"", $output, $ret);
        if ($ret !== 0) {
            throw new Exception("Cannot convert SMILES to MOLFILE: " . $smiles);
        }
        return join("\n", $output);
    }

    public static function convertMolfileToInchIKey(string $molfile) : string {
        global $IP;
        $binFolder = "$IP/extensions/ChemExtension/resources/indigo";
        if (!GeneralTools::startsWith($molfile, "\n") && !GeneralTools::startsWith($molfile, "\r\n")) {
            $molfile = "\n".$molfile;
        }
        $molfile = MolfileProcessor::cleanUp($molfile);
        $tmpFile = sys_get_temp_dir() . '/'. uniqid();
        file_put_contents($tmpFile, $molfile);
        exec("java -jar $binFolder/convertMolfileToInchIKey.jar \"$tmpFile\"", $output, $ret);
        unlink($tmpFile);
        if ($ret !== 0) {
            throw new Exception("Cannot convert MOLFILE to InchIKey: " . $output);
        }
        return trim($output[0]);
    }
}