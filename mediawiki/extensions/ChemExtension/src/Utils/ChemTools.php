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

    public static function getChemFormIdFromTitle(Title $title): string
    {
        $titleText = $title->getText();
        $titleText = str_replace(['Molecule', 'Reaction'], '', $titleText);
        return trim($titleText);
    }
}