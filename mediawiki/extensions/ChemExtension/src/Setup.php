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
                $baseScript . '/ve.oo.ui.ketcher-widget.js',
                $baseScript . '/ve.oo.ui.ketcher-dialog.js',
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
        $formula = self::startsWith($formula, "\n") ? $formula : "\n$formula";
        $formula = self::endsWith($formula, "\n") ? $formula : "$formula\n";

        global $wgScriptPath;
        $formula = urlencode($formula);
        $path = "$wgScriptPath/extensions/ChemExtension/rdkit";
        $output = "<iframe class=\"$cssClass\" src=\"$path/index-formula.html?formula=$formula\" width='$width' height='$height'></iframe>";
        return array( $output, 'noparse' => true, 'isHTML' => true );
    }

    public static function isEnabled()
    {
        return defined('DIQA_WIKI_FARM');
    }

    private static function startsWith( $haystack, $needle ) {
        $length = strlen( $needle );
        return substr( $haystack, 0, $length ) === $needle;
    }
    private static function endsWith( $haystack, $needle ) {
        $length = strlen( $needle );
        if( !$length ) {
            return true;
        }
        return substr( $haystack, -$length ) === $needle;
    }

}