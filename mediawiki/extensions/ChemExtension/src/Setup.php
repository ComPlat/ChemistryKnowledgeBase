<?php
namespace DIQA\ChemExtension;

use DIQA\ChemExtension\Literature\DOIRenderer;
use DIQA\ChemExtension\ParserFunctions\RenderFormula;
use DIQA\ChemExtension\ParserFunctions\RenderLiterature;
use DIQA\ChemExtension\ParserFunctions\RenderMoleculeLink;
use DIQA\ChemExtension\ParserFunctions\ShowMoleculeCollection;
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
                $baseScript . '/rgroups.js',
                $baseScript . '/render-chemform-tooltip.js',
                $baseScript . '/ve.oo.model.tools.js',
                $baseScript . '/ve.extend.js',
                $baseScript . '/ve.insert-commands.js',
                $baseScript . '/ve.oo-ui.rgroups-lookup.js',
                $baseScript . '/ve.oo.ui.ketcher-widget.js',
                $baseScript . '/ve.oo.ui.ketcher-dialog.js',
                $baseScript . '/ve.oo.ui.molecule-rests-widget.js',
                $baseScript . '/ve.oo.ui.molecule-rests-dialog.js',
                $baseScript . '/oo.ui.rgroups-display-widget.js',
                $baseScript . '/oo.ui.show-rgroups-dialog.js',
                $baseScript . '/rerender-chemform.js',
                $baseScript . '/show-rests.js',

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
        self::outputLiteratureReferences($out);

    }

    public static function onParserFirstCallInit( Parser $parser ) {
        $parser->setHook( 'chemform', [ RenderFormula::class, 'renderFormula' ] );
        $parser->setFunctionHook( 'literature', [ RenderLiterature::class, 'renderLiterature' ] );
        $parser->setFunctionHook( 'moleculelink', [ RenderMoleculeLink::class, 'renderMoleculeLink' ] );
        $parser->setFunctionHook( 'showMoleculeCollection', [ ShowMoleculeCollection::class, 'renderMoleculeCollectionTable' ] );
    }

    private static function outputLiteratureReferences(OutputPage $out): void {
        if (count(RenderLiterature::$LITERATURE_REFS) === 0) {
            return;
        }
        $out->addHTML("<h2>Literature</h2>");
        $doiRenderer = new DOIRenderer();
        foreach (RenderLiterature::$LITERATURE_REFS as $l) {
            $output = $doiRenderer->render($l['data']);
            $out->addHTML($output);
        }
    }
}