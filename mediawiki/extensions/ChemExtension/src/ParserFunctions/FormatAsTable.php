<?php

namespace DIQA\ChemExtension\ParserFunctions;

use Parser;

class FormatAsTable {

    public static function formatAsTable(Parser $parser)
    {
        $parametersAsStringArray = func_get_args();
        array_shift($parametersAsStringArray); // get rid of Parser
        $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);
        $rows = explode(";", $parameters['']);
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