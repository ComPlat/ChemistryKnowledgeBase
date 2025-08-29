<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Literature\DOIRenderer;
use DIQA\ChemExtension\Literature\DOIResolver;
use DIQA\ChemExtension\Literature\LiteratureRepository;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use MediaWiki\MediaWikiServices;
use Parser;
use eftec\bladeone\BladeOne;
use ParserOptions;
use RequestContext;

class DOIInfoBox
{

    public static $DOI_INFO_BOX = [];

    /**
     * Renders a DOI infobox
     *
     * @param Parser $parser
     * @param $doi
     * @return array
     * @throws Exception
     */
    public static function renderDOIInfoBox(Parser $parser, $doi): array
    {
        try {
            $hooksContainer = MediaWikiServices::getInstance()->getHookContainer();
            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
            $repo = new LiteratureRepository($dbr);
            if ($doi !== '') {
                $literature = $repo->getLiterature($doi);
                if (is_null($literature)) {
                    $doiResolver = new DOIResolver();
                    $data = $doiResolver->resolve($doi);
                } else {
                    $data = $literature['data'];
                }

                if ($data === '__placeholder__') {
                    // should not happen
                    return ["$doi was not yet resolved.", 'noparse' => true, 'isHTML' => true];
                }
            } else {
                $data = new \stdClass();
                $data->DOI = '';
            }
            $doiRenderer = new DOIRenderer();
            $html = $doiRenderer->renderDOIInfoTemplate($data);


            self::$DOI_INFO_BOX[] = $html;

            $hooksContainer->run('ExtendSubtitle', [WikiTools::sanitizeHTML($html)]);
            return ['', 'noparse' => true, 'isHTML' => true];
        } catch(Exception $e) {
            $html = self::getBlade()->run ( "error", ['message' => $e->getMessage()]);
            return [$html, 'noparse' => true, 'isHTML' => true];
        }
    }

    public static function onExtendSearchFulltext(& $extText) {

        foreach (self::$DOI_INFO_BOX as $html) {
            $extText .= $html;
        }
        $extText = \Sanitizer::stripAllTags($extText);

    }

    /**
     * @throws Exception
     */
    private static function getBlade(): BladeOne
    {
        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        if (!is_writable($cache)) {
            throw new Exception("cache folder for blade engine is not writeable: $cache");
        }
        return new BladeOne ( $views, $cache );
    }


}
