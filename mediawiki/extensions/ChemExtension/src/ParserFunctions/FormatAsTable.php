<?php

namespace DIQA\ChemExtension\ParserFunctions;

use Parser;

class FormatAsTable {

    public static function formatAsTable(Parser $parser)
    {
        $parametersAsStringArray = func_get_args();
        array_shift($parametersAsStringArray); // get rid of Parser
        $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);
        if (!isset($parameters[''])) {
            return ['', 'noparse' => true, 'isHTML' => true];
        }
        $rows = explode(";", $parameters['']);
        if (isset($parameters['row'])) {
            $content = trim($rows[$parameters['row']]) ?? '';
            if ($content === '') {
                return ['', 'noparse' => true, 'isHTML' => true];
            }
            $add = $parameters['add'] ?? null;
            $parts = explode(',', $content);
            $parts = array_map(fn($e) => is_null($add) ? $e : (float)$e + (float)$add, $parts);
            return [join(', ', $parts), 'noparse' => true, 'isHTML' => true];
        }
        $result = '<table class="ce-center-aligned-table" inner="true">';
        foreach($rows as $row) {
            $result .= '<tr>';
            $columns = explode(",",$row);
            foreach($columns as $c) {
                $result .= "<td>$c</td>";
            }
            $result .= '</tr>';
        }
        $result .= '</table>';
        $result = strip_tags($result, "<table><tr><td>");
        return [$result, 'noparse' => true, 'isHTML' => true];
    }

}