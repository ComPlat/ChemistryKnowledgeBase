<?php
namespace DIQA\ChemExtension;

use DIQA\ChemExtension\Experiments\ExperimentRepository;
use DIQA\ChemExtension\Literature\DOIRenderer;
use DIQA\ChemExtension\ParserFunctions\ExperimentLink;
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
                $baseScript . '/rgroups.js',
                $baseScript . '/client-ajax-endpoints.js',
                $baseScript . '/render-chemform-tooltip.js',
                $baseScript . '/ve.oo.model.tools.js',
                $baseScript . '/ve.extend.js',
                $baseScript . '/ve.insert-commands.js',
                $baseScript . '/ve.oo-ui.rgroups-lookup.js',
                $baseScript . '/ve.oo.ui.ketcher-widget.js',
                $baseScript . '/ve.oo.ui.ketcher-dialog.js',
                $baseScript . '/ve.oo.ui.molecule-rgroups-widget.js',
                $baseScript . '/ve.oo.ui.molecule-rgroups-dialog.js',
                $baseScript . '/oo.ui.rgroups-display-widget.js',
                $baseScript . '/oo.ui.show-rgroups-dialog.js',
                $baseScript . '/rerender-chemform.js',
                $baseScript . '/ve-form-input.js',
                $baseScript . '/ve-form-input-dialog.js',
                $baseScript . '/ve-form-input-widget.js',

            ],
            'styles' => [ 'skins/main.css' ],
            'dependencies' => ['ext.visualEditor.core', 'ext.diqa.qtip', 'jquery.ui', 'ext.pageforms.main', 'ext.pageforms.popupformedit'],
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
        $out->addJsConfigVars('experiments', ExperimentRepository::getInstance()->getAll());
        DOIRenderer::outputLiteratureReferences($out);
    }

    public static function onParserFirstCallInit( Parser $parser ) {
        $parser->setHook( 'chemform', [ RenderFormula::class, 'renderFormula' ] );
        $parser->setFunctionHook( 'literature', [ RenderLiterature::class, 'renderLiterature' ] );
        $parser->setFunctionHook( 'moleculelink', [ RenderMoleculeLink::class, 'renderMoleculeLink' ] );
        $parser->setFunctionHook( 'showMoleculeCollection', [ ShowMoleculeCollection::class, 'renderMoleculeCollectionTable' ] );
        $parser->setFunctionHook( 'experimentlist', [ ExperimentList::class, 'renderExperimentList'] );
        $parser->setFunctionHook( 'experimentlink', [ ExperimentLink::class, 'renderExperimentLink' ] );
    }


}