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
}
