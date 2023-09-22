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
            } else if (count($keyValue) === 2){
                $parameters[trim($keyValue[0])] = trim($keyValue[1]);
            } else {
                $parameters[trim($keyValue[0])] = trim(implode('=', array_slice($keyValue, 1)));
            }
        }
        return $parameters;
    }

    public static function serializeFunction($fnName, $params) {
        $firstParam = $params[''] ?? '';
        unset($params['']);
        $keyValues = [];
        foreach($params as $key => $value) {
            $keyValues[] = "$key=$value";
        }
        return "{{#$fnName: $firstParam|".join('|', $keyValues)."}}";
    }

    public function replaceFunction($wikitext, $fnName, $param, $value, $newParams): string {

        $regex = str_replace('__fn_name__', $fnName, self::PARSER_FUNCTION_REGEX);
        return preg_replace_callback($regex,  function($matches) use ($fnName, $param, $value, $newParams) {
            $arguments = $this->parseFunction($fnName, $matches[0])[0];
            if (($arguments[$param] ?? '') === $value) {
               return self::serializeFunction($fnName, $newParams);
            } else {
                return $matches[0];
            }
        }, $wikitext);
    }
}