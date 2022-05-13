<?php
namespace DIQA\ChemExtension\Endpoints;

use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use Philo\Blade\Blade;
use Wikimedia\ParamValidator\ParamValidator;

class GetRDKit extends SimpleHandler {

    public function run() {


        $views = __DIR__ . '/../../views';
        $cache = __DIR__ . '/../../cache';
        $blade = new Blade ( $views, $cache );

        $params = $this->getValidatedParams();

        $width = intval($params['width']);
        $width = $width - ($width/10);

        $height = intval($params['height']);
        $height = $height - ($height/10);

        global $wgScriptPath;
        $html = $blade->view ()->make ( "rdkit-iframe",
            [
                'width'  => "$width".'px',
                'height' => "$height".'px',
                'wgScriptPath' => $wgScriptPath
            ]
        )->render ();

        return new Response($html);
    }

    public function needsWriteAccess() {
        return false;
    }

    public function getParamSettings() {
        return [

            'width' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],
            'height' => [
                self::PARAM_SOURCE => 'query',
                ParamValidator::PARAM_TYPE => 'string',
                ParamValidator::PARAM_REQUIRED => true,
            ],
        ];
    }
}