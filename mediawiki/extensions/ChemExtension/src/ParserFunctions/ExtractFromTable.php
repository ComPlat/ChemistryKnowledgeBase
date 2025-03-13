<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Utils\GeneralTools;
use Parser;

class ExtractFromTable {

    /**
     * Extract a row from a table. Columns are separated by comma, rows by semi-colon.
     *
     * Required parameter: row=integer: Extracts the given row (zero-based)
     * Optional parameter: add=numeric value. If present, adds given value to every column.
     *
     * Returns comma-separated values
     *
     * @param Parser $parser
     * @return array
     */
    public static function extractFromTable(Parser $parser)
    {
        $parametersAsStringArray = func_get_args();
        array_shift($parametersAsStringArray); // get rid of Parser
        $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);
        if (!isset($parameters['']) || !isset($parameters['row'])) {
            return ['', 'noparse' => true, 'isHTML' => true];
        }
        $rows = explode(";", $parameters['']);

        $content = trim($rows[$parameters['row']]) ?? '';
        if ($content === '') {
            return ['', 'noparse' => true, 'isHTML' => true];
        }
        if (isset($parameters['add']) && !is_numeric($parameters['add'])) {
            return ['', 'noparse' => true, 'isHTML' => true];
        }
        $add = $parameters['add'] ?? null;
        $parts = explode(',', $content);
        $parts = array_map(fn($e) => GeneralTools::toZeroIfVerySmall(is_null($add) ? $e : (float)$e + (float)$add), $parts);
        return [join(', ', $parts), 'noparse' => true, 'isHTML' => true];


    }

}