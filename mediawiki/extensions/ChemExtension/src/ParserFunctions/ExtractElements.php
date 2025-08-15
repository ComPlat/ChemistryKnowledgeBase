<?php

namespace DIQA\ChemExtension\ParserFunctions;

use Parser;
use eftec\bladeone\BladeOne;

class ExtractElements
{
    public static function extractElements(Parser $parser): array
    {
        try {
            $parametersAsStringArray = func_get_args();
            array_shift($parametersAsStringArray); // get rid of Parser
            $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

            if (!isset($parameters['formula'])) {
                throw new Exception("required parameter: 'formula'");
            }

            $formula = $parameters['formula'];
            $elements = [];
            $res = preg_match_all('/[A-Z][a-z]?/', $formula, $matches);
            if ($res !== false) {
                $elements = array_unique($matches[0]);
            }
            return [implode(',', $elements), 'noparse' => false, 'isHTML' => false];
        } catch (Exception $e) {
            $html = self::getBlade()->run("error", ['message' => $e->getMessage()]);
            return [$html, 'noparse' => true, 'isHTML' => true];
        }

    }

    /**
     * @throws Exception
     */
    private static function getBlade(): BladeOne
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        if (!is_writable($cache)) {
            throw new Exception("cache folder for blade engine is not writeable: $cache");
        }
        return new BladeOne ( $views, $cache );
    }
}