<?php
namespace DIQA\ChemExtension;

class Setup {

    public static function initModules() {

        global $wgResourceModules;
        global $IP;

        $wgResourceModules['ext.diqa.chemextension'] = array(
            'localBasePath' => "$IP",
            'remoteExtPath' => 'ChemExtension',
            'position' => 'bottom',
            'scripts' => [],
            'styles' => [ '/extensions/ChemExtension/skins/main.css'],
            'dependencies' => [],
        );
    }

    public static function onBeforePageDisplay( \OutputPage $out, \Skin $skin ) {

        $out->addModules('ext.diqa.chemextension');
    }

    public static function onParserFirstCallInit( \Parser $parser ) {

        // Create a function hook associating the "example" magic word with renderExample()
        $parser->setFunctionHook( 'chemicalformula', [ self::class, 'renderChemicalFormula' ] );
    }

    // Render the output of {{#example:}}.
    public static function renderChemicalFormula(\Parser $parser, $formula = '' ) {

        // The input parameters are wikitext with templates expanded.
        // The output should be wikitext too.
        global $wgScriptPath;
        $path = "$wgScriptPath/extensions/ChemExtension/cheminfo";
        $output = "<iframe class=\"chemformula\" src=\"$path/index.html?formula=$formula\" width='800px' height='400px'></iframe>";

        return array( $output, 'noparse' => true, 'isHTML' => true );
    }
}