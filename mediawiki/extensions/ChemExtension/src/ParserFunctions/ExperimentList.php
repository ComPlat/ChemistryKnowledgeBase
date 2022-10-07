<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Experiments\ExperimentRenderer;
use DIQA\ChemExtension\Utils\WikiTools;
use Parser;
use Exception;

class ExperimentList
{

    /**
     * Renders a list of experiments. Lets the user edit the experiments in VE mode.
     *
     * @param Parser $parser
     *
     * @return array
     * @throws Exception
     */
    public static function renderExperimentList(Parser $parser): array
    {
        $parametersAsStringArray = func_get_args();
        array_shift($parametersAsStringArray); // get rid of Parser
        $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

        $renderer = new ExperimentRenderer([
            'page' => WikiTools::getCurrentTitle(),
            'form' => $parameters['form'],
            'showEditLink' => true
        ]);
        $html = $renderer->renderInViewMode($parameters, $parser);
        if (WikiTools::isInVisualEditor()) {
            $html = str_replace(array("<tbody>","</tbody>"), "", $html);
        }
        return [str_replace("\n", "", $html), 'noparse' => true, 'isHTML' => true];
    }

}
