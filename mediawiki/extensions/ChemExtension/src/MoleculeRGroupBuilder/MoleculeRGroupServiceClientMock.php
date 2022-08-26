<?php
namespace DIQA\ChemExtension\MoleculeRGroupBuilder;

class MoleculeRGroupServiceClientMock implements MoleculeRGroupServiceClient {

    function buildMolecules(string $molfile, array $moleculeRests)
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
        $o = new \stdClass();

        $o->molecules = [];
        for($i = 0; $i < count($moleculeRests); $i++) {
            $molecule = new \stdClass();
            $molecule->rests = $moleculeRests[$i];
            $molecule->molfile = $molfile;
            $o->molecules[] = $molecule;
        }
        return $o;
    }
}
