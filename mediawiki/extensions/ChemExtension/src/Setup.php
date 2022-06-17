<?php
namespace DIQA\ChemExtension;

use DIQA\ChemExtension\Pages\ChemFormRepository;
use MediaWiki\MediaWikiServices;

class Setup {

    public static function initModules() {

        global $wgResourceModules;
        global $IP;

        $baseScript = 'scripts';
        $wgResourceModules['ext.diqa.chemextension'] = array(
            'localBasePath' => "$IP/extensions/ChemExtension",
            'remoteExtPath' => 'ChemExtension',
            'position' => 'bottom',
            'scripts' => [
                $baseScript . '/render-chemform-tooltip.js',
                $baseScript . '/ve.extend.js',
                $baseScript . '/ve.insert-chem-form.js',
                $baseScript . '/ve.oo.ui.ketcher-widget.js',
                $baseScript . '/ve.oo.ui.ketcher-dialog.js',
            ],
            'styles' => [ 'skins/main.css' ],
            'dependencies' => ['ext.visualEditor.core', 'ext.diqa.qtip'],
        );

        $wgResourceModules['ext.diqa.qtip'] = array(
            'localBasePath' => "$IP/extensions/ChemExtension",
            'remoteExtPath' => 'ChemExtension',
            'position' => 'bottom',
            'scripts' => [
                $baseScript . '/libs/jquery.qtip.js',
            ],
            'styles' => [ 'scripts/libs/jquery.qtip.css' ],
            'dependencies' => [],
        );
    }

    public static function onBeforePageDisplay( \OutputPage $out, \Skin $skin ) {

        $out->addModules('ext.diqa.chemextension');

        global $wgScriptPath;
        $random = uniqid();
        $path = "$wgScriptPath/extensions/ChemExtension/ketcher/index-ketcher.html?random=$random";
        $output = "<iframe style=\"display: none;\" id=\"ketcher-renderer\" src=\"$path\"></iframe>";
        $out->addHTML($output);

    }

    public static function onParserFirstCallInit( \Parser $parser ) {
        $parser->setHook( 'chemform', [ self::class, 'renderIframe' ] );
    }

    public static function renderIframe( $formula, array $arguments, \Parser $parser, \PPFrame $frame ) {

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
        $attributes['isreaction'] = $arguments['isreaction'] == '1' ? "true" : "false";

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
            'random' => uniqid()
        ] );
        global $wgScriptPath;
        $attributes['src'] = "$wgScriptPath/extensions/ChemExtension/ketcher/index-formula.html?$queryString";
        $serializedAttributes = self::serializeAttributes($attributes);
        $output = "<iframe $serializedAttributes></iframe>";

        return array( $output, 'noparse' => true, 'isHTML' => true );
    }

    private static function serializeAttributes(array $attributes): string
    {
        $html = '';
        foreach($attributes as $key => $value) {
            $value = str_replace('"', '&quot;', $value);
            $html .= " $key='" . $value . "'";
        }
        return $html;
    }

}