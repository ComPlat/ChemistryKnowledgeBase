<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Experiments\ExperimentLinkRenderer;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Parser;
use Philo\Blade\Blade;
use Title;

class ExperimentLink
{

    /**
     * Renders a list of experiments. Experiments cannot be edited in VE mode.
     *
     * @param Parser $parser
     *
     * @return array
     * @throws Exception
     */
    public static function renderExperimentLink(Parser $parser): array
    {
        try {
            $parametersAsStringArray = func_get_args();
            array_shift($parametersAsStringArray); // get rid of Parser
            $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

            if (!isset($parameters['page']) || !isset($parameters['form']) || !isset($parameters['name'])) {
                throw new Exception("required parameters: 'page', 'name' and 'form'");
            }

            $page = $parameters['page'];
            $indices = isset($parameters['index']) && $parameters['index'] != '' ? $parameters['index'] : false;

            $renderer = new ExperimentLinkRenderer([
                'page' => Title::newFromText($page),
                'form' => $parameters['form'],
                'name' => $parameters['name'],
                'index' => $indices !== false ? self::parseIndices($indices) : null,
            ]);
            $html = $renderer->render();
            return [WikiTools::sanitizeHTML($html), 'noparse' => true, 'isHTML' => true];
        } catch(Exception $e) {
            $html = self::getBlade()->view ()->make ( "error", ['message' => $e->getMessage()])->render ();
            return [$html, 'noparse' => true, 'isHTML' => true];
        }
    }

    private static function parseIndices($indicesAsString): array
    {
        $indicesArray = explode(",", $indicesAsString);
        $indicesWithAllNumbers = array_map(function ($e) {
            $parts = explode("-", $e);
            if (count($parts) === 1) {
                return [(int)$parts[0]];
            } else if (count($parts) === 2) {
                return range((int)$parts[0], (int)$parts[1]);
            } else {
                return [0];
            }
        }, $indicesArray);
        return ArrayTools::flatten($indicesWithAllNumbers);
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
