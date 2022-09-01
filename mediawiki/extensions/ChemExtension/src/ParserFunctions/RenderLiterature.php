<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Literature\DOIRenderer;
use DIQA\ChemExtension\Literature\DOIResolver;
use DIQA\ChemExtension\Literature\DOITools;
use DIQA\ChemExtension\Literature\LiteratureRepository;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use MediaWiki\MediaWikiServices;
use Parser;

class RenderLiterature
{

    public static $LITERATURE_REFS = [];
    private static $LITERATURE_REF_COUNTER = 0;

    public static function renderLiterature(Parser $parser)
    {

        try {
            $parametersAsStringArray = func_get_args();
            array_shift($parametersAsStringArray); // get rid of Parser
            $parameters = ParserfunctionParser::parseArguments($parametersAsStringArray);
            $doiParameterValue = $parameters['doi'] ?? null;
            $doi = DOITools::parseDOI($doiParameterValue);
            if (is_null($doi)) {
                throw new Exception("DOI is empty");
            }

            $doiData = self::resolveDOI($doi);

        } catch (Exception $e) {
            $output = $e->getMessage();
            return [$output, 'noparse' => true, 'isHTML' => true];
        }

        if (!array_key_exists($doi, self::$LITERATURE_REFS)) {
            self::$LITERATURE_REF_COUNTER++;
            self::$LITERATURE_REFS[$doi] = ['data' => $doiData];
        }

        $doiRenderer = new DOIRenderer();

        if (WikiTools::isInVisualEditor()) {
            $output = "<span>[" . DOITools::generateReferenceIndex($doiData) . "]</span>";
        } else {
            $output = $doiRenderer->renderReference($doiData);

        }
        return [$output, 'noparse' => true, 'isHTML' => true];
    }

    /**
     * @param $doi
     * @return mixed
     * @throws Exception
     */
    public static function resolveDOI($doi)
    {
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $repo = new LiteratureRepository($dbr);
        $literature = $repo->getLiterature($doi);

        if (is_null($literature)) {
            $doiResolver = new DOIResolver();
            $doiData = $doiResolver->resolve($doi);
        } else {
            $doiData = $literature['data'];
        }
        return $doiData;
    }

}
