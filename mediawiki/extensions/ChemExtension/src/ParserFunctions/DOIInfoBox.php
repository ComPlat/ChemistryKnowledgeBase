<?php

namespace DIQA\ChemExtension\ParserFunctions;

use DIQA\ChemExtension\Literature\DOIRenderer;
use DIQA\ChemExtension\Literature\DOIResolver;
use DIQA\ChemExtension\Literature\LiteratureRepository;
use DIQA\ChemExtension\Utils\WikiTools;
use Exception;
use MediaWiki\MediaWikiServices;
use Parser;
use Philo\Blade\Blade;
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

            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
            $repo = new LiteratureRepository($dbr);
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
            $doiRenderer = new DOIRenderer();
            $html = $doiRenderer->renderDOIInfoTemplate($data);


            self::$DOI_INFO_BOX[] = $html;

            \Hooks::run('ExtendSubtitle', [WikiTools::sanitizeHTML($html)]);
            return ['', 'noparse' => true, 'isHTML' => true];
        } catch(Exception $e) {
            $html = self::getBlade()->view ()->make ( "error", ['message' => $e->getMessage()])->render ();
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
