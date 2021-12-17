<?php
namespace DIQA\ChemExtension;

class Setup {

    public static function initModules() {

        global $wgResourceModules;
        global $IP;

        $baseScript = '/extensions/ChemExtension/scripts';
        $wgResourceModules['ext.diqa.chemextension'] = array(
            'localBasePath' => "$IP",
            'remoteExtPath' => 'ChemExtension',
            'position' => 'bottom',
            'scripts' => [
                $baseScript . '/ve.extend.js',
                $baseScript . '/ve.insert-chem-form.js',
            ],
            'styles' => [ '/extensions/ChemExtension/skins/main.css'],
            'dependencies' => ['ext.visualEditor.core'],
        );
    }

    public static function onBeforePageDisplay( \OutputPage $out, \Skin $skin ) {

        $out->addModules('ext.diqa.chemextension');

    }

    public static function onParserFirstCallInit( \Parser $parser ) {
        $parser->setHook( 'chemform', [ self::class, 'renderIframe' ] );
    }

    public static function renderIframe( $formula, array $arguments, \Parser $parser, \PPFrame $frame ) {
        $cssClass = "chemformula";
        $width = $arguments['width'] ?? "800px";
        $height = $arguments['height'] ?? "400px";
        $nofloat = $arguments['nofloat'] ?? false;
        if ($nofloat == "true") {
            $cssClass = "chemformula-nofloat";
        }
        global $wgScriptPath;
        $formula = urlencode($formula);
        $path = "$wgScriptPath/extensions/ChemExtension/rdkit";
        $output = "<iframe class=\"$cssClass\" src=\"$path/index.html?formula=$formula\" width='$width' height='$height'></iframe>";
        return array( $output, 'noparse' => true, 'isHTML' => true );
    }

    public static function isEnabled()
    {
        return defined('DIQA_WIKI_FARM');
    }

}