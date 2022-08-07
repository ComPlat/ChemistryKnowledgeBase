<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Literature\DOIRenderer;
use DIQA\ChemExtension\Literature\DOIResolver;
use DIQA\ChemExtension\Literature\LiteratureRepository;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;

class RenderLiterature {

    public static $LITERATURE_REFS = [];
    private static $LITERATURE_REF_COUNTER = 0;

    public static function renderLiterature(\Parser $parser, $param1, $doiParameter = '')
    {

        try {
            $doiResolver = new DOIResolver();

            $parts = explode('=', $doiParameter);
            $doiParameterValue = $parts[1];
            $urlParts = parse_url($doiParameterValue);
            if (!array_key_exists('path', $urlParts)) {
                throw new Exception("DOI could not be interpreted: $doiParameterValue");
            }
            $doi = $urlParts['path'];
            $doi = strpos($doi, '/') === 0 ? substr($doi, 1) : $doi;

            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
                DB_REPLICA
            );
            $repo = new LiteratureRepository($dbr);
            $literature = $repo->getLiterature($doi);

            if (is_null($literature)) {
                $doiData = $doiResolver->resolve($doi);
            } else {
                $doiData = $literature['data'];
            }

        } catch (Exception $e) {
            $output = $e->getMessage();
            return [$output, 'noparse' => true, 'isHTML' => true];
        }
        if (!array_key_exists($doi, self::$LITERATURE_REFS)) {
            self::$LITERATURE_REF_COUNTER++;
            self::$LITERATURE_REFS[$doi] = ['data' => $doiData, 'index' => self::$LITERATURE_REF_COUNTER];
        }

        $doiRenderer = new DOIRenderer();

        if (WikiTools::isInVisualEditor()) {
            $output = "<span>[" . self::$LITERATURE_REFS[$doi]['index'] . "]</span>";
        } else {
            $output = $doiRenderer->renderReference(self::$LITERATURE_REFS[$doi]['index']);

        }
        return [$output, 'noparse' => true, 'isHTML' => true];
    }
}
