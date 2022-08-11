<?php

namespace DIQA\ChemExtension\MoleculeRestBuilder;

interface MoleculeRestServiceClient {

    /**
     * Transforms a molecule with rests into concrete molecules using the given molecule rests.
     *
     * @param $molfile string Molecule in molfile V3000 format
     * @param $moleculeRests array of molecule rests
     * [
     *      [ R1 => "...", R2 => "..."],
     *      [ R1 => "...", R2 => "..."],
     *      ...
     * ]
     * @return mixed
     */
    function buildMolecules(string $molfile, array $moleculeRests);

}
