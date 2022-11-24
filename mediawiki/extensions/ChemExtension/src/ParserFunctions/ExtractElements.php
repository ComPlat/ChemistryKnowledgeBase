<?php

namespace DIQA\ChemExtension\ParserFunctions;

use Parser;
use Philo\Blade\Blade;

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
            $html = self::getBlade()->view()->make("error", ['message' => $e->getMessage()])->render();
            return [$html, 'noparse' => true, 'isHTML' => true];
        }

    }

    /**
     * @throws Exception
     */
    private static function getBlade(): Blade
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        if (!is_writable($cache)) {
            throw new Exception("cache folder for blade engine is not writeable: $cache");
        }
        return new Blade ( $views, $cache );
    }
}