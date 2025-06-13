<?php

namespace DIQA\ChemExtension\Xlsx;

use DIQA\ChemExtension\Experiments\ExperimentXlsImporter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PHPUnit\Framework\TestCase;

class TestImport extends TestCase {

    public function testImport(): void
    {
        global $IP;
        $reader = new Xlsx();
        $spreadsheet = $reader->load($IP . '/extensions/ChemExtension/test/xlsx/data/testuv.xlsx');
        if ($spreadsheet->sheetNameExists('ChemWiki')) {
            $sheet = $spreadsheet->getSheetByName('ChemWiki');
        } else {
            $sheet = $spreadsheet->getActiveSheet();
        }
        $importer = new ExperimentXlsImporter($sheet);
        $dataToImport = $importer->getDataToImport('Ultraviolett_Visuell_experiments');

        $expected = <<<TEXT
{{Ultraviolett_Visuell
|additives conc=7000
|include=true
}}{{Ultraviolett_Visuell
|additives conc=5
|include=true
}}
TEXT;
        $this->assertEquals(self::normalize($expected), self::normalize($dataToImport['rowsContent']));
    }

    private static function normalize($s) {
        return str_replace(["\r"], "", $s);
    }
}
