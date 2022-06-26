<?php
namespace DIQA\ChemExtension;

use DIQA\ChemExtension\Literature\DOIRenderer;
use OutputPage;
use Skin;
use Parser;

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

    public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {

        $out->addModules('ext.diqa.chemextension');
        self::outputKetcher($out);
        self::outputLiteratureReferences($out);

    }

    public static function onParserFirstCallInit( Parser $parser ) {
        $parser->setHook( 'chemform', [ ParserFunctions::class, 'renderIframe' ] );
        $parser->setFunctionHook( 'literature', [ ParserFunctions::class, 'renderLiterature' ] );
    }

    private static function outputKetcher(OutputPage $out) {
        global $wgScriptPath;
        $random = uniqid();
        $path = "$wgScriptPath/extensions/ChemExtension/ketcher/index-ketcher.html?random=$random";
        $output = "<iframe style=\"display: none;\" id=\"ketcher-renderer\" src=\"$path\"></iframe>";
        $out->addHTML($output);
    }

    /**
     * @param OutputPage $out
     */
    private static function outputLiteratureReferences(OutputPage $out): void
    {
        $out->addHTML("<h2>Literature</h2>");
        $doiRenderer = new DOIRenderer();
        foreach (ParserFunctions::$LITERATURE_REFS as $l) {
            $output = $doiRenderer->render($l['data'], $l['index']);
            $out->addHTML($output);
        }
    }

}