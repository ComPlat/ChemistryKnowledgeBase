<?php
namespace DIQA\ChemExtension;

use DIQA\ChemExtension\Experiments\ExperimentRepository;
use DIQA\ChemExtension\Literature\DOIRenderer;
use DIQA\ChemExtension\ParserFunctions\ExperimentLink;
use DIQA\ChemExtension\ParserFunctions\ExtractElements;
use DIQA\ChemExtension\ParserFunctions\RenderFormula;
use DIQA\ChemExtension\ParserFunctions\RenderLiterature;
use DIQA\ChemExtension\ParserFunctions\RenderMoleculeLink;
use DIQA\ChemExtension\ParserFunctions\ShowMoleculeCollection;
use DIQA\ChemExtension\ParserFunctions\ExperimentList;
use OutputPage;
use Parser;
use Skin;

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
                $baseScript . '/special.create-topic.js',
                $baseScript . '/rgroups.js',
                $baseScript . '/client-ajax-endpoints.js',
                $baseScript . '/render-chemform-tooltip.js',
                $baseScript . '/ve.oo.model.tools.js',
                $baseScript . '/ve.extend.js',
                $baseScript . '/ve.insert-commands.js',
                $baseScript . '/ve.oo-ui.rgroups-lookup.js',
                $baseScript . '/ve.oo-ui.inchikey-lookup.js',
                $baseScript . '/ve.oo.ui.ketcher-widget.js',
                $baseScript . '/ve.oo.ui.ketcher-dialog.js',
                $baseScript . '/ve.oo.ui.molecule-rgroups-widget.js',
                $baseScript . '/ve.oo.ui.molecule-rgroups-dialog.js',
                $baseScript . '/oo.ui.rgroups-display-widget.js',
                $baseScript . '/oo.ui.show-rgroups-dialog.js',
                $baseScript . '/rerender-chemform.js',
                $baseScript . '/ve.oo-ui.initialize.js',
                $baseScript . '/ve.oo-ui.add-experiment-dialog.js',
                $baseScript . '/ve.oo-ui.add-experiment-widget.js',
                $baseScript . '/ve.oo-ui.add-experiment-link-dialog.js',
                $baseScript . '/ve.oo.ui.molecule-link-dialog.js',
                $baseScript . '/ve.oo.ui.molecule-link-widget.js',

            ],
            'styles' => [ 'skins/main.css' ],
            'dependencies' => ['ext.visualEditor.core', 'ext.diqa.qtip', 'jquery.ui', 'ext.pageforms.main', 'ext.pageforms.popupformedit',
                'mediawiki.widgets.TitlesMultiselectWidget'],
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

        $wgResourceModules['ext.diqa.chemextension.pf'] = array(
            'localBasePath' => "$IP/extensions/ChemExtension",
            'remoteExtPath' => 'ChemExtension',
            'position' => 'bottom',
            'scripts' => [],
            'styles' => [ 'skins/pf.css' ],
            'dependencies' => [],
        );
    }

    public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {

        $out->addModules('ext.diqa.chemextension');
        $out->addJsConfigVars('experiments', ExperimentRepository::getInstance()->getAll());
        DOIRenderer::outputLiteratureReferences($out);
        RenderFormula::outputMoleculeReferences($out);

        if (!is_null($out->getTitle()) && $out->getTitle()->isSpecial("FormEdit")) {
            $out->addModules('ext.diqa.chemextension.pf');
        }
    }

    public static function onParserFirstCallInit( Parser $parser ) {
        $parser->setHook( 'chemform', [ RenderFormula::class, 'renderFormula' ] );
        $parser->setFunctionHook( 'literature', [ RenderLiterature::class, 'renderLiterature' ] );
        $parser->setFunctionHook( 'moleculelink', [ RenderMoleculeLink::class, 'renderMoleculeLink' ] );
        $parser->setFunctionHook( 'showMoleculeCollection', [ ShowMoleculeCollection::class, 'renderMoleculeCollectionTable' ] );
        $parser->setFunctionHook( 'experimentlist', [ ExperimentList::class, 'renderExperimentList'] );
        $parser->setFunctionHook( 'experimentlink', [ ExperimentLink::class, 'renderExperimentLink' ] );
        $parser->setFunctionHook( 'extractElements', [ ExtractElements::class, 'extractElements' ] );


    }

    public static function assignValueToMagicWord( &$parser, &$cache, &$magicWordId, &$ret ) {
        if ( $magicWordId === 'counter' ) {
            static $counter = 1;
            $ret = $counter++;
        }
        return true;
    }


    public static function declareVarIds( &$customVariableIds ) {
        $customVariableIds[] = 'counter';
    }
}