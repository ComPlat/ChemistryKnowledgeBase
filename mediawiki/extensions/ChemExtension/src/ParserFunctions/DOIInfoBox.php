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

class DOIInfoBox
{

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
            $doiRenderer = new DOIRenderer();
            $templateCall = $doiRenderer->renderDOIInfoTemplate($data);

            $parserNew = new Parser();
            $parserOutput = $parserNew->parse($templateCall, $parser->getTitle(), new ParserOptions());
            $html = $parserOutput->getText(['enableSectionEditLinks' => false, 'unwrap' => true]);

            \Hooks::run('ExtendSubtitle', [WikiTools::sanitizeHTML($html)]);
            return ['', 'noparse' => true, 'isHTML' => true];
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


}
