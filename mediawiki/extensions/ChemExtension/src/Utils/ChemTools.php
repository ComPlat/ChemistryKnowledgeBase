<?php

namespace DIQA\ChemExtension\Utils;

use SMW\DIWikiPage;
use SMW\DIProperty;

class ChemTools {

    const CAS_PATTERN = '/^\d{2,7}-\d{2}-\d$/';
    const CHEMFORM_ID = '/^(Molecule:)?\d{6,}$/';

    public static function isCASNumber($s): bool
    {
        return preg_match(self::CAS_PATTERN, trim($s), $matches) === 1;
    }

    public static function isChemformId($s): bool
    {
        return preg_match(self::CHEMFORM_ID, trim($s), $matches) === 1;
    }

    public static function getChemFormIdFromPageTitle(?string $pageTitle) {
        if (preg_match("/Molecule:(\\d+)/", $pageTitle, $matches) !== 1) {
            if (preg_match("/Reaction:(\\d+)/", $pageTitle, $matches) !== 1) {
                return null;
            }
        }
        return $matches[1] ?? null;
    }

    public static function getNamesOfMolecule($moleculeTitle) {
        $moleculeTitleWP = DIWikiPage::newFromTitle($moleculeTitle);
        $res = smwfGetStore()->getPropertyValues($moleculeTitleWP,
            DIProperty::newFromUserLabel("Abbreviation"));
        if (count($res) > 0) {
            $first = reset($res);
            return $first->getString();
        } else {
            $moleculeTitleWP = DIWikiPage::newFromTitle($moleculeTitle);
            $res = smwfGetStore()->getPropertyValues($moleculeTitleWP,
                DIProperty::newFromUserLabel("Trivialname"));
            if (count($res) > 0) {
                $first = reset($res);
                return $first->getString();
            } else {
                $moleculeTitleWP = DIWikiPage::newFromTitle($moleculeTitle);
                $res = smwfGetStore()->getPropertyValues($moleculeTitleWP,
                    DIProperty::newFromUserLabel("IUPACName"));
                if (count($res) > 0) {
                    $first = reset($res);
                    return $first->getString();
                }
            }
        }
        return null;
    }

    public static function isEmptySVGImage($svg) {
        $xml = simplexml_load_string($svg);
        return $xml->count() === 0;
    }

    public static function isInchIKey($key) {
        return preg_match('/\w{14}-\w{10}-\w/', $key) === 1;
    }

}