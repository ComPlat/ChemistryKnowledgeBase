<?php

namespace DIQA\ChemExtension\Utils;

class MolfileProcessor
{

    const MOLFILE_COUNT_LINE_START = 'M  V30 COUNTS ';
    const MOLFILE_BEGIN_BOND_BLOCK_LINE = 'M  V30 BEGIN BOND';

    /**
     * clear bond lines with bond type 8(any), 9(coord), or 10(hydrogen)
     * @param $mol
     * @return mixed|string
     */
    public static function cleanUp($mol)
    {
        $linesToRemove = [];
        $lines = explode("\n", $mol);
        for($i = 0; $i < count($lines); $i++) {
            if (strpos($lines[$i], self::MOLFILE_COUNT_LINE_START) === 0) {
                $countIndex = $i;
            }
            if (strpos($lines[$i], self::MOLFILE_BEGIN_BOND_BLOCK_LINE) === 0) {
                $bondIndex = $i;
            }
        }

        for($i = $bondIndex+1; $i < count($lines); $i++) {
            if (preg_match('/^M\s+V30\s+\d+\s+(8|9|10)/', $lines[$i]) === 1) {
                $linesToRemove[] = $i;
            }
        }

        if (count($linesToRemove) === 0) {
            return $mol;
        }

        preg_match('/^(M\s+V30\s+COUNTS\s+\d+\s+)(\d+)(\s+.)*/', $lines[$countIndex], $matches);
        $newCount = ((int)$matches[2]) - count($linesToRemove);
        $lines[$countIndex] = preg_replace('/^(M\s+V30\s+COUNTS\s+\d+)\s+(\d+)\s+(.)*/', '$1 '.$newCount.' $3', $lines[$countIndex]);

        $result = [];
        for($i = 0; $i < count($lines); $i++) {
            if (!in_array($i, $linesToRemove)) {
                $result[] = $lines[$i];
            }
        }
        return implode("\n", $result);
    }

    public static function getRGroupIds($formula) {

        preg_match_all('/RGROUPS=\((\d+)\s*(\d+)/', $formula, $matches);

        $rGroupIds = [];
        if (!isset($matches[2])) {
            return [];
        }
        foreach($matches[2] as $m) {
            if (!in_array("r$m", $rGroupIds)) {
                $rGroupIds[] = "r$m";
            }
        }
        sort($rGroupIds);
        return $rGroupIds;
    }

    public static function hasRGroups($formula): bool
    {
        preg_match_all('/RGROUPS=\((\d+)\s*(\d+)/', $formula, $matches);
        return count($matches[0]) > 0;
    }

    /**
     * Returns the unique ID for a molecule.
     * For a concrete molecule this is always the inchiKey. For a molecule template this is
     * the smiles string + the R-Groups in sorted order
     *
     * @param $formula molfile or RXN
     * @param $smiles smiles
     * @param $inchiKey inchiKey
     * @return mixed|string
     */
    public static function generateMoleculeKey($formula, $smiles, $inchiKey) {
        $key = $inchiKey;
        if (is_null($inchiKey) || $inchiKey === '') {
            $key = $smiles . implode('', MolfileProcessor::getRGroupIds($formula));
        }
        return $key;
    }

    /**
     * Returns true if the formula contains a reaction.
     *
     * @param $molOrRxn
     * @return bool
     */
    public static function isReactionFormula($molOrRxn): bool
    {
        return strpos(trim(str_replace(["\n", "\r"], "", $molOrRxn)), '$RXN') === 0;
    }
}
