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
            $parameters = self::parseArgumentsFromString($c);
            $results[] = $parameters;
        }

        return $results;
    }

    public static function parseArgumentsFromString($parametersAsString): array
    {
        $parametersAsStringArray = explode('|', $parametersAsString);
        $parametersAsStringArray = array_map(function($e) { return trim($e);}, $parametersAsStringArray);
        return self::parseArguments($parametersAsStringArray);
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

    public static function serializeArguments($parameters): string
    {
        $keyValues = [];
        foreach($parameters as $key => $value) {
            $keyValues[] = "$key=$value";
        }
        return join("\n|", $keyValues);
    }

    public static function serializeFunction($fnName, $params): string
    {
        $firstParam = $params[''] ?? '';
        unset($params['']);
        return "{{#$fnName: $firstParam|".self::serializeArguments($params)."}}";
    }

    public function replaceFunction($wikitext, $fnName, $param, $value, $newParams): string {

        $regex = str_replace('__fn_name__', $fnName, self::PARSER_FUNCTION_REGEX);
        return preg_replace_callback($regex,  function($matches) use ($fnName, $param, $value, $newParams) {
            $arguments = $this->parseFunction($fnName, $matches[0])[0];
            if (($arguments[$param] ?? '') === $value) {
               return self::serializeFunction($fnName, array_merge($arguments, $newParams));
            } else {
                return $matches[0];
            }
        }, $wikitext);
    }
}