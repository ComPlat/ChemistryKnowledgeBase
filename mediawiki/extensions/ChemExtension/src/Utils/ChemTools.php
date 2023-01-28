<?php

namespace DIQA\ChemExtension\Utils;

use Title;

class ChemTools {

    const CAS_PATTERN = '/^\d{2,7}-\d{2}-\d$/';
    const CHEMFORM_ID = '/^\d{6,}$/';

    public static function isCASNumber($s): bool
    {
        return preg_match(self::CAS_PATTERN, trim($s), $matches) === 1;
    }

    public static function isChemformId($s): bool
    {
        return preg_match(self::CHEMFORM_ID, trim($s), $matches) === 1;
    }

    public static function getChemFormIdFromPageTitle(string $pageTitle) {
        if (preg_match("/Molecule:(\\d+)/", $pageTitle, $matches) !== 1) {
            if (preg_match("/Reaction:(\\d+)/", $pageTitle, $matches) !== 1) {
                return null;
            }
        }
        return $matches[1] ?? null;
    }

}