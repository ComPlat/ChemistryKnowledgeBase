<?php

namespace DIQA\ChemExtension\ParserFunctions;

class ParserFunctionParser {

    const PARSER_FUNCTION_REGEX = '/\{\{#__fn_name__:([^}]*)\}\}/m';

    public function parseFunction($name, $wikitext): array
    {
        $regex = str_replace('__fn_name__', $name, self::PARSER_FUNCTION_REGEX);
        preg_match_all($regex, $wikitext, $matches);
        $parserFunctionCalls = $matches[1] ?? [];

        $results = [];
        foreach ($parserFunctionCalls as $c) {
            $parametersAsStringArray = explode('|', $c);
            $parametersAsStringArray = array_map(function($e) { return trim($e);}, $parametersAsStringArray);
            $parameters = self::parseArguments($parametersAsStringArray);
            $results[] = $parameters;
        }

        return $results;
    }

    public static function parseArguments($parametersAsStringArray): array
    {
        $parameters = [];
        foreach($parametersAsStringArray as $p) {
            if (trim($p) === '') {
                continue;
            }
            $keyValue = explode('=', $p);
            if (count($keyValue) === 1) {
                $parameters[''] = trim($keyValue[0]);
            } else {
                $parameters[trim($keyValue[0])] = trim($keyValue[1]);
            }
        }
        return $parameters;
    }
}