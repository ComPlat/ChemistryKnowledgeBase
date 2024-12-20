<?php

namespace DIQA\ChemExtension\Indigo;

use Exception;

class IndigoTool {

    public static function convertToMolfile(string $smiles) : string {
        global $IP;
        $binFolder = "$IP/extensions/ChemExtension/resources/indigo";
        exec("java -jar $binFolder/indigo.jar \"$smiles\"", $output, $ret);
        if ($ret !== 0) {
            throw new \Exception("Cannot convert SMILES to MOLFILE: " . $smiles);
        }
        return join("\n", $output);
    }
}