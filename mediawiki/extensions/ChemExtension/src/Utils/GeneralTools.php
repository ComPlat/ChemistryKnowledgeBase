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
}