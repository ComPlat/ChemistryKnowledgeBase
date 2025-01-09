<?php

namespace DIQA\ChemExtension\ParserFunctions;

use Exception;
use Parser;

class Selectivity
{

    /**
     * Calculates Selectivity. Takes an arbitrary amount of numeric values as pf-parameters
     *
     * @param Parser $parser
     * @return array
     * @throws Exception
     */
    public static function calculateSelectivity(Parser $parser): array
    {
        try {
            $parametersAsStringArray = func_get_args();
            array_shift($parametersAsStringArray); // get rid of Parser

            $valueIsEmpty = fn($e) => trim($e) === '' || trim($e) === '0';
            $nonEmptyValues = array_filter($parametersAsStringArray, fn($e) => !$valueIsEmpty($e));
            $nonEmptyValues = array_map(fn($e) => QValue::extractValue($e), $nonEmptyValues);
            $totalSum = array_sum($nonEmptyValues);

            if (count($nonEmptyValues) > 1) {
                $values = [];
                foreach($parametersAsStringArray as $p) {
                    if ($valueIsEmpty($p)) {
                        $values[] = "0%";
                    } else {
                        $values[] = number_format((float)($p / $totalSum)*100, 1, '.', '')."%";
                    }
                }
                $result = implode('; ', $values);
            } else {
                $result = 'n/a';
            }

            return [$result, 'noparse' => true, 'isHTML' => false];
        } catch (Exception $e) {
            return ['-error on calculation-', 'noparse' => true, 'isHTML' => false];
        }
    }

}
