<?php

namespace DIQA\ChemExtension\Utils;

class GeneralTools {

    public static function endsWith( $haystack, $needle ) {
        $length = strlen( $needle );
        if( !$length ) {
            return true;
        }
        return substr( $haystack, -$length ) === $needle;
    }

    public static function startsWith ($string, $startString)
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    public static function roundNumber($number, $digits = 3) {
        if (abs($number) < 0.00000000001) {
            return 0;
        }
        return round($number, $digits);
    }

}