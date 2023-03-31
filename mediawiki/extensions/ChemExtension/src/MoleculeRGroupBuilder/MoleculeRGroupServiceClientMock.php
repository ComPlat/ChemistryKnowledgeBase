<?php
namespace DIQA\ChemExtension\MoleculeRGroupBuilder;

use DIQA\ChemExtension\Pages\ChemForm;

class MoleculeRGroupServiceClientMock implements MoleculeRGroupServiceClient {

    function buildMolecules(string $molfile, array $rGroups)
    {
        $molfile = <<<MOLFILE

  -INDIGO-08042212082D

  0  0  0  0  0  0  0  0  0  0  0 V3000
M  V30 BEGIN CTAB
M  V30 COUNTS 6 6 0 0 0
M  V30 BEGIN ATOM
M  V30 1 C 1.25985 -4.72507 0.0 0
M  V30 2 C 2.99015 -4.72459 0.0 0
M  V30 3 C 2.12664 -4.22497 0.0 0
M  V30 4 C 2.99015 -5.72553 0.0 0
M  V30 5 C 1.25985 -5.73002 0.0 0
M  V30 6 C 2.12882 -6.22503 0.0 0
M  V30 END ATOM
M  V30 BEGIN BOND
M  V30 1 2 3 1
M  V30 2 2 4 2
M  V30 3 1 1 5
M  V30 4 1 2 3
M  V30 5 2 5 6
M  V30 6 1 6 4
M  V30 END BOND
M  V30 END CTAB
M  END

MOLFILE;
        $o = [];

        for($i = 0; $i < count($rGroups); $i++) {
            $molecule = new \stdClass();

            foreach($rGroups[$i] as $r => $value) {
                $molecule->{$r} = $value;
            }
            $molecule->mdl = $molfile;
            $molecule->smiles = 'C1C=CC=CC=1';
            $molecule->inchikey = md5(uniqid());
            $molecule->inchi = md5(uniqid());

            $o[] = [
                'chemForm' => ChemForm::fromMolOrRxn($molecule->mdl, $molecule->smiles, $molecule->inchi, $molecule->inchikey),
                'rGroups' =>  ['r1' => 'H']
            ];
        }
        return $o;
    }

    function getMetadata(string $molfile): array
    {
        return ['molecularMass' => 77.03912516, 'molecularFormula' => 'C6H6'];
    }

    function getAvailableRGroups(): array
    {
        return [
            "DMF",
            "CO2Et",
            "EtO2C",
            "COOEt",
            "EtOOC",
            "OiBu",
            "iBuO",
            "tBu",
            "nBu",
            "iPr",
            "OC",
            "H2O",
            "py",
            "DMFFMD"
        ];
    }
}
