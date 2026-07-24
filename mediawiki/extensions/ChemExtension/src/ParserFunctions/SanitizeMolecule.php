<?php

namespace DIQA\ChemExtension\ParserFunctions;

use Exception;
use Parser;

class SanitizeMolecule {

    public static function sanitizeMolecule(Parser $parser): array
    {
        try {
            $parametersAsStringArray = func_get_args();
            array_shift($parametersAsStringArray); // get rid of Parser

            $results = array_map(fn($e) => str_replace(['[', ']'], ['&#65339;', '&#65341;'], $e),
                $parametersAsStringArray);

            return [join(' ', $results), 'noparse' => true, 'isHTML' => false];
        } catch (Exception $e) {
            return ['-error on calculation-', 'noparse' => true, 'isHTML' => false];
        }
    }
}
