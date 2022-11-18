<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Experiments\ExperimentListRenderer;
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
        try {
            $parametersAsStringArray = func_get_args();
            array_shift($parametersAsStringArray); // get rid of Parser
            $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

            if (!isset($parameters['form']) || !isset($parameters['name'])) {
                throw new Exception("required parameters: 'name' and 'form'");
            }

            $title = WikiTools::getCurrentTitle($parser);
            if (is_null($title)) {
                throw new Exception("could not identify current title");
            }
            $renderer = new ExperimentListRenderer([
                'page' => $title,
                'form' => $parameters['form'],
                'name' => $parameters['name'],
                'index' => null
            ]);
            $html = $renderer->render();
            return [WikiTools::sanitizeHTML($html), 'noparse' => true, 'isHTML' => true];

        } catch (Exception $e) {
            $html = self::getBlade()->view()->make("error", ['message' => $e->getMessage()])->render();
            return [$html, 'noparse' => true, 'isHTML' => true];
        }
    }

}
