<?php

namespace DIQA\ChemExtension;

use DIQA\ChemExtension\Literature\DOIRenderer;
use DIQA\ChemExtension\Literature\DOIResolver;
use DIQA\ChemExtension\Literature\LiteratureRepository;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use Exception;
use MediaWiki\MediaWikiServices;
use Parser;
use PPFrame;

class ParserFunctions
{

    public static $LITERATURE_REFS = [];
    private static $LITERATURE_REF_COUNTER = 0;

    /**
     * @throws Exception
     */
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

        if (self::isInVisualEditor()) {
            $output = "[" . self::$LITERATURE_REFS[$doi]['index'] . "]";
        } else {
            $output = $doiRenderer->renderReference(self::$LITERATURE_REFS[$doi]['index']);

        }
        return [ $output, 'noparse' => true, 'isHTML' => true];
    }

    private static function isInVisualEditor()
    {
        global $wgRequest;
        return (strpos($wgRequest->getText( 'title' ), '/v3/page/html/') !== false
            || strpos($wgRequest->getText( 'title' ), '/v3/transform/wikitext/to/html/') !== false);
    }

    public static function renderIframe($formula, array $arguments, Parser $parser, PPFrame $frame)
    {

        $attributes = [];

        $attributes['class'] = "chemformula";
        $attributes['width'] = $arguments['width'] ?? "300px";
        $attributes['height'] = $arguments['height'] ?? "200px";
        $float = $arguments['float'] ?? 'none';
        if ($float !== 'none') {
            $attributes['style'] = "float: $float;";
        }

        $attributes['smiles'] = base64_encode($arguments['smiles'] ?? '');
        $attributes['formula'] = base64_encode($formula);
        $attributes['isreaction'] = $arguments['isreaction'] == '1' || $arguments['isreaction'] == 'true' ? "true" : "false";

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );
        $chemFormRepo = new ChemFormRepository($dbr);
        $key = $arguments['inchikey'];
        if (is_null($key) || $key === '') {
            $key = $arguments['smiles'];
        }
        $attributes['chemFormId'] = $chemFormRepo->getChemFormId($key);

        $queryString = http_build_query([
            'width' => $attributes['width'],
            'height' => $attributes['height'],
            'chemformid' => $attributes['chemFormId'],
            'isreaction' => $attributes['isreaction'],
            'random' => uniqid()
        ]);
        global $wgScriptPath;
        $attributes['src'] = "$wgScriptPath/extensions/ChemExtension/ketcher/index-formula.html?$queryString";
        $serializedAttributes = self::serializeAttributes($attributes);
        $output = "<iframe $serializedAttributes></iframe>";

        return array($output, 'noparse' => true, 'isHTML' => true);
    }

    private static function serializeAttributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            $value = str_replace('"', '&quot;', $value);
            $html .= " $key='" . $value . "'";
        }
        return $html;
    }
}