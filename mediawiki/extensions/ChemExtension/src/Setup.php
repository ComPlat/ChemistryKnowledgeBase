<?php
namespace DIQA\ChemExtension;

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
                $baseScript . '/ve.extend.js',
                $baseScript . '/ve.insert-chem-form.js',
                $baseScript . '/ve.oo.ui.ketcher-widget.js',
                $baseScript . '/ve.oo.ui.ketcher-dialog.js',
            ],
            'styles' => [ 'skins/main.css'],
            'dependencies' => ['ext.visualEditor.core'],
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
        $cssClass = "chemformula";
        $width = $arguments['width'] ?? "800px";
        $height = $arguments['height'] ?? "400px";
        $nofloat = $arguments['nofloat'] ?? false;
        if ($nofloat == "true") {
            $cssClass = "chemformula-nofloat";
        }
        $float = $arguments['float'] ?? 'none';
        $style = '';
        if ($float !== 'none') {
            $style = "style=\"float: $float;\"";
        }
        $smiles = $arguments['smiles'] ?? '';
        $smiles = base64_encode($smiles);

        global $wgScriptPath;
        $random = uniqid();
        $path = "$wgScriptPath/extensions/ChemExtension/ketcher/index-formula.html?width=$width&height=$height&random=$random";
        $output = "<iframe $style class=\"$cssClass\" src=\"$path\" width='$width' height='$height' smiles='$smiles'></iframe>";
        return array( $output, 'noparse' => true, 'isHTML' => true );
    }

}