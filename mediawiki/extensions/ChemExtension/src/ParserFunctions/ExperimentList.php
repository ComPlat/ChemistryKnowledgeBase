<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Experiments\ExperimentListRenderer;
use DIQA\ChemExtension\Experiments\ExperimentNotExistsException;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Parser;
use Philo\Blade\Blade;

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
                'description' => $parameters['description'] ?? '',
                'index' => null
            ]);
            $html = $renderer->render();
            return [WikiTools::sanitizeHTML($html), 'noparse' => true, 'isHTML' => true];

        } catch(ExperimentNotExistsException $e) {
            $html = self::getBlade()->view()->make("error", ['message' => $e->getMessage(),
                'data' => [ 'experimentName' => $e->getExperimentName(), 'code' => $e->getCode()]])->render();
            return [$html, 'noparse' => true, 'isHTML' => true];
        } catch (Exception $e) {
            $html = self::getBlade()->view()->make("error", ['message' => $e->getMessage(), 'code' => $e->getCode()])->render();
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
