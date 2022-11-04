<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Experiments\ExperimentRenderer;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\WikiTools;
use Parser;
use Exception;
use Title;

class ExperimentLink {

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
        $parametersAsStringArray = func_get_args();
        array_shift($parametersAsStringArray); // get rid of Parser
        $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

        if (!isset($parameters['page']) || !isset($parameters['form']) || !isset($parameters['name'])) {
            return ["missing parameters: 'page' and/or 'form'", 'noparse' => true, 'isHTML' => true];
        }

        $page = $parameters['page'];
        $indices = $parameters['index'] ?? false;

        $renderer = new ExperimentRenderer([
            'page' => Title::newFromText($page),
            'form' => $parameters['form'],
            'name' => $parameters['name'],
            'index' => $indices !== false ? self::parseIndices($indices) : null,
            'showEditLink' => false
        ]);
        $html = $renderer->renderInViewMode();
        if (WikiTools::isInVisualEditor()) {
            $html = str_replace(array("<tbody>","</tbody>"), "", $html);
        }
        return [str_replace("\n", "", $html), 'noparse' => true, 'isHTML' => true];
    }

    private static function parseIndices($indicesAsString): array
    {
        $indicesArray = explode(",", $indicesAsString);
        $indicesWithAllNumbers = array_map(function($e) {
            $parts = explode("-", $e);
            if (count($parts) === 1) {
                return [(int) $parts[0]];
            } else  if (count($parts) === 2) {
                return range((int)$parts[0], (int)$parts[1]);
            } else {
                return [0];
            }
        }, $indicesArray);
        return ArrayTools::flatten($indicesWithAllNumbers);
    }


}
