<?php

namespace DIQA\ChemExtension\MoleculeRGroupBuilder;

interface MoleculeRGroupServiceClient {

    /**
     * Transforms a molecule with rGroups into concrete molecules using the given molecule rGroups.
     *
     * @param $molfile string Molecule in molfile V3000 format
     * @param $rGroups array of molecule rGroups
     * [
     *      [ r1 => "...", r2 => "...", ...],
     *      [ r1 => "...", r2 => "...", ...],
     *      ...
     * ]
     * @return mixed
     */
    function buildMolecules(string $molfile, array $rGroups);

    /**
     * Returns metadata for a molecule, e.g. molecular mass
     * @param $molfile string Molecule in molfile V3000 format
     * @return array of metadata
     */
    function getMetadata(string $molfile): array;

    /**
     * Returns available RGroups
     * @return array
     */
    function getAvailableRGroups() : array;

}
