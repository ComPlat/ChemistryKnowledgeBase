<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Experiments\ExperimentLinkRenderer;
use DIQA\ChemExtension\Experiments\ExperimentRepository;
use DIQA\ChemExtension\Utils\ArrayTools;
use DIQA\ChemExtension\Utils\QueryUtils;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use Parser;
use Philo\Blade\Blade;
use Title;
use SMWDataItem;

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
    public static function renderExperimentLink(Parser $parser, $selectExperimentQuery): array
    {
        try {
            $parametersAsStringArray = func_get_args();
            array_shift($parametersAsStringArray); // get rid of Parser
            $parameters = ParserFunctionParser::parseArguments($parametersAsStringArray);

            if (!isset($parameters['form'])) {
                throw new Exception("required parameters: 'form'");
            }

            $title = WikiTools::getCurrentTitle($parser);
            if (is_null($title)) {
                throw new Exception("could not identify current title");
            }

            $renderer = new ExperimentLinkRenderer([
                'page' => $title,
                'form' => $parameters['form'],
                'description' => $parameters['description'] ?? '- please enter description -',
                'templateData' => self::getTemplateData($parameters, urldecode($selectExperimentQuery))
            ]);
            $html = $renderer->render();
            return [WikiTools::sanitizeHTML($html), 'noparse' => true, 'isHTML' => true];
        } catch(Exception $e) {
            $html = self::getBlade()->view ()->make ( "error", ['message' => $e->getMessage()])->render ();
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

    /**
     * @param array $parameters
     * @return array
     */
    private static function getTemplateData(array $parameters, $selectExperimentQuery): array
    {
        $restrictToPages = $parameters['restrictToPages'] ?? false;
        $sort = $parameters['sort'] ?? '';
        $order = $parameters['order'] ?? '';
        $restrictToPagesQuery = '';
        if ($restrictToPages !== false && trim($restrictToPages) !== '') {
            $restrictToPageConstraint = array_map(function($p) {
                    $title = Title::newFromText(trim($p));
                    return "[[{$title->getPrefixedText()}]]";
                }, explode(",", $restrictToPages));
            $restrictToPagesQuery = "[[BasePageName::<q>".implode(" OR ", $restrictToPageConstraint)."</q>]]";
        }
        $selectExperimentQuery = trim($selectExperimentQuery) == '' ? '' : $selectExperimentQuery;

        $experimentType = ExperimentRepository::getInstance()->getExperimentType($parameters['form']);
        $printRequests = [];
        $properties = $experimentType->getProperties();
        foreach($properties as $p => $templateParam) {
            $printRequests[] = QueryUtils::newPropertyPrintRequest($p);
        }
        $selectExperimentQuery = self::buildQuery($parameters['form'], $selectExperimentQuery, $restrictToPagesQuery);
        $results = QueryUtils::executeBasicQuery($selectExperimentQuery, $printRequests, [ 'sort' => $sort, 'order' => $order ]);
        $rows = [];
        while ($row = $results->getNext()) {
            $column = reset($row);
            $oneRow = [];
            while($column !== false) {
                $templateParam = $properties[$column->getPrintRequest()->getLabel()] ?? null;
                $dataItem = $column->getNextDataItem();
                $column = next($row);
                if (is_null($templateParam)) continue;
                if ($dataItem === false) continue;
                if ($dataItem->getDIType() == SMWDataItem::TYPE_BLOB) {
                    $oneRow[$templateParam] = $dataItem->getString();
                } else if ($dataItem->getDIType() == SMWDataItem::TYPE_WIKIPAGE) {
                    $oneRow[$templateParam] = $dataItem->getTitle()->getPrefixedText();
                } else if ($dataItem->getDIType() == SMWDataItem::TYPE_NUMBER) {
                    $oneRow[$templateParam] = $dataItem->getNumber();
                }

                //FIXME: add other types
            }
            $rows[] = $oneRow;
        }
        return $rows;
    }

    /**
     * @param Title|null $experimentPageTitle
     * @param $queryToSelectExperiments
     * @return string
     */
    private static function buildQuery($mainTemplate, $queryToSelectExperiments, $restrictQuery): string
    {

        $orPartQueries = array_map(function ($q) use ($mainTemplate, $restrictQuery) {
            return
                "[[-Has subobject::<q>[[Category:$mainTemplate]]</q>]] $q $restrictQuery [[Included::true]]";
        }, explode(" OR ", $queryToSelectExperiments));
        return implode(" OR ", $orPartQueries);
    }
}
