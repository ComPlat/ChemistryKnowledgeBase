<?php

namespace DIQA\ChemExtension\ParserFunctions;

use Exception;
use Parser;

class QValue
{

    /**
     * Extracts value of a quantity. (removes unit)
     *
     * @param Parser $parser
     * @return array
     * @throws Exception
     */
    public static function quantityValue(Parser $parser): array
    {
        try {
            $parametersAsStringArray = func_get_args();
            array_shift($parametersAsStringArray); // get rid of Parser

            $result = '';
            $first = reset($parametersAsStringArray);
            if ($first !== false) {
                $result = self::extractValue($first);
            }

            return [$result, 'noparse' => true, 'isHTML' => false];
        } catch (Exception $e) {
            return ['-error on calculation-', 'noparse' => true, 'isHTML' => false];
        }
    }

    public static function extractValue($first)
    {
        preg_match("/-?([0-9]*\.)?[0-9]*/", $first, $matches);
        return $matches[0] ?? '';
    }

}
