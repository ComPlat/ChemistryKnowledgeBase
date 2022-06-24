<?php

namespace DIQA\ChemExtension\Pages;

use DIQA\ChemExtension\Utils\MolfileProcessor;

class InchIGenerator {

    public function getInchI($mol) {
        global $IP;
        $results = ['InChI' => '', 'InChIKey' => ''];
        $inchToolPath = "$IP/extensions/ChemExtension/resources/inchi/inchi-1";

        $mol = MolfileProcessor::cleanUp($mol);

        $tmpFile = tempnam("/tmp/", uniqid());
        file_put_contents($tmpFile, $mol);
        shell_exec("$inchToolPath -key $tmpFile 2>&1");

        $content = file_get_contents("$tmpFile.txt");
        $lines = explode("\n", $content);
        foreach($lines as $line) {
            if (strpos($line, "=") === false) {
                continue;
            }
            $parts = explode("=", $line);
            $results[$parts[0]] = $parts[1];
        }
        return $results;
    }
}
