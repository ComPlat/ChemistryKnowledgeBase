<?php
namespace DIQA\ChemExtension;

use DIQA\ChemExtension\Experiments\ExperimentRepository;
use DIQA\ChemExtension\Literature\DOIRenderer;
use DIQA\ChemExtension\NavigationBar\InvestigationFinder;
use DIQA\ChemExtension\NavigationBar\NavigationBar;
use DIQA\ChemExtension\ParserFunctions\DOIInfoBox;
use DIQA\ChemExtension\ParserFunctions\ExperimentLink;
use DIQA\ChemExtension\ParserFunctions\ExperimentList;
use DIQA\ChemExtension\ParserFunctions\ExtractElements;
use DIQA\ChemExtension\ParserFunctions\FormatAsTable;
use DIQA\ChemExtension\ParserFunctions\RenderFormula;
use DIQA\ChemExtension\ParserFunctions\RenderLiterature;
use DIQA\ChemExtension\ParserFunctions\RenderMoleculeLink;
use DIQA\ChemExtension\ParserFunctions\ShowMoleculeCollection;
use RequestContext;
use OutputPage;
use Parser;
use Skin;
use SMW\ApplicationFactory;

class Setup {

    private static $cachedQueries = [];
    private static $subTitleExtension = '';

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
                $baseScript . '/pf-extensions.js',
                $baseScript . '/ve.oo-ui.add-experiment-dialog.js',
                $baseScript . '/ve.oo-ui.add-experiment-widget.js',
                $baseScript . '/ve.oo-ui.add-experiment-link-dialog.js',
                $baseScript . '/ve.oo.ui.molecule-link-dialog.js',
                $baseScript . '/ve.oo.ui.molecule-link-widget.js',
                $baseScript . '/breadcrumb.js',

            ],
            'styles' => [ 'skins/main.css', 'skins/skin-modifications.css' ],
            'dependencies' => ['ext.visualEditor.core', 'ext.diqa.qtip', 'jquery.ui', 'ext.pageforms.main', 'ext.pageforms.popupformedit',
                'mediawiki.widgets.TitlesMultiselectWidget', 'ext.categoryTree', 'ext.categoryTree.styles'],
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

        $wgResourceModules['ext.diqa.md5'] = array(
            'localBasePath' => "$IP/extensions/ChemExtension",
            'remoteExtPath' => 'ChemExtension',
            'position' => 'bottom',
            'scripts' => [
                $baseScript . '/libs/md5.js',
            ],
            'styles' => [],
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

        $wgResourceModules['ext.diqa.chemextension.modify-molecule'] = array(
            'localBasePath' => "$IP/extensions/ChemExtension",
            'remoteExtPath' => 'ChemExtension',
            'position' => 'bottom',
            'scripts' => ['scripts/special.modify-molecule.js'],
            'styles' => [],
            'dependencies' => ['ext.diqa.chemextension'],
        );
    }

    public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {
        global $wgTitle;
        $b = new NavigationBar($wgTitle);
        $out->addSubtitle('<div class="ce-subtitle-content">'.$b->getPageType() . self::$subTitleExtension."</div>");

        $out->addModules('ext.diqa.chemextension');
        $out->addModules('ext.diqa.md5');
        $out->addJsConfigVars('experiments', ExperimentRepository::getInstance()->getAll());
        DOIRenderer::outputLiteratureReferences($out);
        RenderFormula::outputMoleculeReferences($out);
        InvestigationFinder::renderInvestigationList($out);

        if (!is_null($out->getTitle()) && $out->getTitle()->isSpecial("FormEdit")) {
            $out->addModules('ext.diqa.chemextension.pf');
        }
        if (!is_null($out->getTitle()) && $out->getTitle()->isSpecial("ModifyMolecule")) {
            $out->addModules('ext.diqa.chemextension.modify-molecule');
        }

    }

    public static function onSkinAfterContent( &$data, Skin $skin ) {
        global $wgTitle;
        $b = new NavigationBar($wgTitle);
        if (!$wgTitle->isSpecial('FormEdit')) {
            $data .= $b->getNavigationBar();
            $data .= $b->getCollapsedNavigationBar();
        }
        global $wgOut;
        $navBarStatus = RequestContext::getMain()->getRequest()->getCookie('mw.chem-extension.navbar-expanded');
        if ($navBarStatus === 'expanded') {
            $wgOut->addInlineStyle('div.container-fluid div.row { margin-left: 400px !important; }');
        }
    }

    public static function extendSubtitle($html)
    {
        self::$subTitleExtension .= $html;
    }

    public static function onParserFirstCallInit( Parser $parser ) {
        $parser->setHook( 'chemform', [ RenderFormula::class, 'renderFormula' ] );
        $parser->setFunctionHook( 'literature', [ RenderLiterature::class, 'renderLiterature' ] );
        $parser->setFunctionHook( 'moleculelink', [ RenderMoleculeLink::class, 'renderMoleculeLink' ] );
        $parser->setFunctionHook( 'showMoleculeCollection', [ ShowMoleculeCollection::class, 'renderMoleculeCollectionTable' ] );
        $parser->setFunctionHook( 'experimentlist', [ ExperimentList::class, 'renderExperimentList'] );
        $parser->setFunctionHook( 'experimentlink', [ ExperimentLink::class, 'renderExperimentLink' ] );
        $parser->setFunctionHook( 'extractElements', [ ExtractElements::class, 'extractElements' ] );
        $parser->setFunctionHook( 'doiinfobox', [ DOIInfoBox::class, 'renderDOIInfoBox' ] );
        $parser->setFunctionHook( 'formatAsTable', [ FormatAsTable::class, 'formatAsTable' ] );

        self::registerShowCachedHandler($parser);
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

    /**
     * @param Parser $parser
     * @throws \MWException
     */
    private static function registerShowCachedHandler(Parser $parser): void
    {
        $applicationFactory = ApplicationFactory::getInstance();
        $parserFunctionFactory = $applicationFactory->newParserFunctionFactory();
        list($name, $definition, $flag) = $parserFunctionFactory->getShowParserFunctionDefinition();
        $showCacheHandler = function (Parser $parser) use ($definition) {
            $args = func_get_args();
            $queryKey = $args[1] . $args[2];
            if (!array_key_exists($queryKey, self::$cachedQueries)) {
                self::$cachedQueries[$queryKey] = call_user_func_array($definition, $args);
            }
            return self::$cachedQueries[$queryKey];
        };
        $parser->setFunctionHook('showcache', $showCacheHandler, $flag);
    }
}