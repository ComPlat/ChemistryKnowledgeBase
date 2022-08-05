<?php

namespace DIQA\ChemExtension\MoleculeRestBuilder;

interface MoleculeRestServiceClient {

    function buildMolecules($molfile, $moleculeRests);

}
