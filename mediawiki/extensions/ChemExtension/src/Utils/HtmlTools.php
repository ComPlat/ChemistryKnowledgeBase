<?php

namespace DIQA\ChemExtension\Utils;

class HtmlTools {

    public static function formatSumFormula($formula) {
        return preg_replace('/(\d+)/', '<sub>$1</sub>', $formula);
    }

    public static function convert2HtmlEntities($string) {
        return mb_encode_numericentity(
                htmlspecialchars_decode(
                    htmlentities($string, ENT_NOQUOTES, 'UTF-8', false)
                    ,ENT_NOQUOTES
                ), [0x80, 0x10FFFF, 0, ~0],
                'UTF-8'
            );
    }
}