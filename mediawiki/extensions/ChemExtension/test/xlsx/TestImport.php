<?php

namespace DIQA\ChemExtension\Xlsx;

use DIQA\ChemExtension\Experiments\ExperimentXlsImporter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PHPUnit\Framework\TestCase;

class TestImport extends TestCase {

    /**
     * @covers Ultraviolett_Visuell_experiments;
     */
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

    /**
     * @covers EC_conversion_of_CO2_experiments;
    */
    public function testImport_EC(): void
    {
        global $IP;
        $reader = new Xlsx();
        $spreadsheet = $reader->load($IP . '/extensions/ChemExtension/test/xlsx/data/Electrochemical_Conversion_Template.xlsx');
        if ($spreadsheet->sheetNameExists('ChemWiki')) {
            $sheet = $spreadsheet->getSheetByName('ChemWiki');
        } else {
            $sheet = $spreadsheet->getActiveSheet();
        }
        $importer = new ExperimentXlsImporter($sheet);
        $dataToImport = $importer->getDataToImport('EC_conversion_of_CO2_experiments');

        $expected = <<<TEXT
{{EC_conversion_of_CO2
|experiment_type=calculated
|cat fixation=fdgdg
|cat carrier=gdgdgd
|cell type=gdgd
|separator=gdgd
|current density=4
|cathodic potential=7
|faradaic amount=5
|electrolysis duration=8
|limiting_turnover_number__CO=1
|maximum_turnover_frequency__CO=2
|turnover_frequency_at_zero_overpotential__CO=3
|faradaic_efficiency__CO=4
|include=false
|BasePageName=Test EChem 1
}}{{EC_conversion_of_CO2
|experiment_type=experimental
|cat conc=5
|cat fixation=fixation
|cat carrier=carreir
|solvent_A__WE=Molecule:12345
|solvent_A__CE=Molecule:67890
|additives__WE=add we 1
|additives__CE=add ce 1
|additives_concentration__WE=4
|additives_concentration__CE=5
|buffer__WE=supporting_electrolyte we 1
|buffer__CE=buffer ce 1
|buffer_concentration__WE=6
|buffer_concentration__CE=7
|supporting_electrolyte__CE=supporting_electrolyte ce 1
|supporting_electrolyte_concentration__WE=8
|supporting_electrolyte_concentration__CE=9
|pH=45
|current density=567
|cathodic potential=123
|faradaic amount=526
|electrolysis duration=89
|limiting_turnover_number__CO=1
|limiting_turnover_number__HCOOH=2
|limiting_turnover_number__H2C2O4=3
|limiting_turnover_number__H2CO=4
|limiting_turnover_number__CH3OH=5
|limiting_turnover_number__H2=6
|limiting_turnover_number__CH4=7
|limiting_turnover_number__C2H4=8
|limiting_turnover_number__CH3CH2OH=9
|include=true
|BasePageName=Test EChem 1
}}
TEXT;
        $this->assertEquals(self::normalize($expected), self::normalize($dataToImport['rowsContent']));

    }

    private static function normalize($s) {
        return str_replace(["\r"], "", $s);
    }
}
