<?php
namespace DIQA\ChemExtension;

use DIQA\ChemExtension\Experiments\ExperimentRepository;
use DIQA\ChemExtension\Literature\DOIRenderer;
use DIQA\ChemExtension\NavigationBar\InvestigationFinder;
use DIQA\ChemExtension\NavigationBar\NavigationBar;
use DIQA\ChemExtension\Pages\ChemFormRepository;
use DIQA\ChemExtension\ParserFunctions\DOIData;
use DIQA\ChemExtension\ParserFunctions\DOIInfoBox;
use DIQA\ChemExtension\ParserFunctions\ExperimentLink;
use DIQA\ChemExtension\ParserFunctions\ExperimentList;
use DIQA\ChemExtension\ParserFunctions\ExtractElements;
use DIQA\ChemExtension\ParserFunctions\FormatAsTable;
use DIQA\ChemExtension\ParserFunctions\RenderFormula;
use DIQA\ChemExtension\ParserFunctions\RenderLiterature;
use DIQA\ChemExtension\ParserFunctions\RenderMoleculeLink;
use DIQA\ChemExtension\ParserFunctions\ShowMoleculeCollection;
use DIQA\ChemExtension\TIB\TagListRenderer;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;
use RequestContext;
use OutputPage;
use Parser;
use Skin;
use SMW\ApplicationFactory;
use SMW\Services\ServicesFactory;
use Title;

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
                $baseScript . '/faceted_search.js',
                $baseScript . '/special.create-pages.js',
                $baseScript . '/rgroups.js',
                $baseScript . '/client-ajax-endpoints.js',
                $baseScript . '/render-chemform-tooltip.js',
                $baseScript . '/ve.oo.model.tools.js',
                $baseScript . '/ve.extend.js',
                $baseScript . '/ve.insert-commands.js',
                $baseScript . '/ve.oo-ui.rgroups-lookup.js',
                $baseScript . '/ve.oo.ui.progress-dialog.js',
                $baseScript . '/ve.oo-ui.inchikey-lookup.js',
                $baseScript . '/ve.oo-ui.tags-input.js',
                $baseScript . '/ve.oo.ui.ketcher-widget.js',
                $baseScript . '/ve.oo.ui.ketcher-dialog.js',
                $baseScript . '/ve.oo.ui.molecule-rgroups-widget.js',
                $baseScript . '/ve.oo.ui.molecule-rgroups-dialog.js',
                $baseScript . '/oo.ui.rgroups-display-widget.js',
                $baseScript . '/oo.ui.show-rgroups-dialog.js',
                $baseScript . '/ve.oo-ui.initialize.js',
                $baseScript . '/ve.oo-ui.experiments.js',
                $baseScript . '/pf-extensions.js',
                $baseScript . '/ve.oo-ui.add-experiment-dialog.js',
                $baseScript . '/ve.oo-ui.add-experiment-widget.js',
                $baseScript . '/ve.oo-ui.add-experiment-link-dialog.js',
                $baseScript . '/ve.oo.ui.molecule-link-dialog.js',
                $baseScript . '/ve.oo.ui.molecule-link-widget.js',
                $baseScript . '/breadcrumb.js',
                $baseScript . '/tagging-selector.js',
                $baseScript . '/ve.oo-ui.annotate-tool.js',
                $baseScript . '/ve.oo-ui.title-multi-select-input.js',

            ],
            'styles' => [ 'skins/main.css', 'skins/skin-modifications.css' ],
            'dependencies' => ['ext.visualEditor.core', 'ext.diqa.qtip', 'jquery.ui', 'ext.pageforms.main', 'ext.pageforms.popupformedit',
                'mediawiki.widgets.TitlesMultiselectWidget', 'ext.categoryTree', 'ext.categoryTree.styles', 'jquery.tablesorter', 'jquery.tablesorter.styles'],
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

        $wgResourceModules['ext.diqa.chemextension.import-page'] = array(
            'localBasePath' => "$IP/extensions/ChemExtension",
            'remoteExtPath' => 'ChemExtension',
            'position' => 'bottom',
            'scripts' => ['scripts/special.import-page.js'],
            'styles' => [],
            'dependencies' => ['ext.diqa.chemextension'],
        );

        $wgResourceModules['ext.diqa.chemextension.redoxinput'] = array(
            'localBasePath' => "$IP/extensions/ChemExtension",
            'remoteExtPath' => 'ChemExtension',
            'position' => 'bottom',
            'scripts' => ['scripts/pf.redox-input.js', 'scripts/pf.redox-input-field.js'],
            'styles' => [],
            'dependencies' => ['ext.diqa.chemextension'],
        );

    }

    public static function onSkinTemplateNavigation( \SkinTemplate $skinTemplate, array &$links ) {
        global $wgTitle, $wgScriptPath;
        if (is_null($wgTitle)) {
            return;
        }
        $links['actions'][] = [
            'text' => "Export as JSON-LD",
            'href' => "$wgScriptPath/rest.php/ChemExtension/v1/json-ld?page=" . urlencode($wgTitle->getPrefixedText())
        ];

        $links['actions'][] = [
            'text' => "Cancel edit",
            'href' => "javascript:ve.init.target.getSurface().emit( 'cancel' );",
            'id'=>'cancelve'
        ];

        global $wgScriptPath;
        $links['actions'][] = [
            'text' => "Edit DOI",
            'href' => "$wgScriptPath/Special:FormEdit/DOI/{$wgTitle->getPrefixedDBkey()}",
            'id'=>'editdoi'
        ];

    }

    public static function onBeforePageDisplay( OutputPage $out, Skin $skin ) {

        self::addSubtitle($out);
        $out->addModules('ext.diqa.chemextension');
        $out->addModules('ext.diqa.md5');
        $out->addJsConfigVars('experiments', ExperimentRepository::getInstance()->getAll());
        DOIRenderer::outputLiteratureReferences($out);
        RenderFormula::outputMoleculeReferences($out);
        InvestigationFinder::renderInvestigationList($out);
        TagListRenderer::renderTagList($out);

        if (!is_null($out->getTitle()) && $out->getTitle()->isSpecial("FormEdit")) {
            $out->addModules('ext.diqa.chemextension.pf');
        }
        if (!is_null($out->getTitle()) && $out->getTitle()->isSpecial("ModifyMolecule")) {
            $out->addModules('ext.diqa.chemextension.modify-molecule');
        }
        if (!is_null($out->getTitle()) && $out->getTitle()->isSpecial("SpecialImportPage")) {
            $out->addModules('ext.diqa.chemextension.import-page');
        }
    }

    private static function createModifyLink() {
        global $wgTitle;
        $link = '';
        $userGroups = MediaWikiServices::getInstance()->getUserGroupManager()->getUserGroups(RequestContext::getMain()->getUser());
        if (in_array('editor', $userGroups)  && !is_null($wgTitle) && $wgTitle->getNamespace() === NS_MOLECULE) {
            $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA );
            $chemFormRepo = new ChemFormRepository($dbr);
            $moleculeKey = $chemFormRepo->getMoleculeKey($wgTitle->getText());
            $modifyMoleculePage = Title::newFromText("ModifyMolecule", NS_SPECIAL);
            $url = $modifyMoleculePage->getFullURL(['moleculeKey'=>$moleculeKey]);
            $link = "<a href=\"$url\">Modify molecule</a>";
        }
        return $link;
    }

    public static function onSkinAfterContent( &$data, Skin $skin ) {
        global $wgTitle;
        if (is_null($wgTitle) || $wgTitle->isSpecial('FormEdit')) {
            return;
        }

        global $wgOut;
        $navBarStatus = RequestContext::getMain()->getRequest()->getCookie('mw.chem-extension.navbar-expanded');
        if (!is_null($wgOut->getTitle()) && $wgOut->getTitle()->isSpecial("Search")) {
            $navBarStatus = 'collapsed';
        }
        $b = new NavigationBar($wgTitle);
        $data .= $b->getNavigationBar($navBarStatus);
        $data .= $b->getCollapsedNavigationBar($navBarStatus);

        $marginWidth = $navBarStatus === 'expanded' ? 400 : 40;
        $inlineCSS = <<<CSS
div.container-fluid div.row { margin-left: {$marginWidth}px !important; }
div#content { padding: 0px !important;}
CSS;

        $wgOut->addInlineStyle($inlineCSS);
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
        $parser->setFunctionHook( 'doidata', [ DOIData::class, 'renderDOIData' ] );

        self::registerShowCachedHandler($parser);
    }

    public static function onFormPrinterSetup( &$pfFormPrinter ) {
        $pfFormPrinter->registerInputType( 'DIQA\ChemExtension\FormInputs\RedoxFormInput' );
    }

    public static function categoryViewerInstance(Title $title, & $html) {
        $html = null;
        if (WikiTools::checkIfInTopicCategory($title)) {
            $html = '';
        }
    }

    public static function categoryList(Title $title, & $label) {
        $label = '';
        if (WikiTools::checkIfInTopicCategory($title)) {
            $label = 'Topics';
        }
    }

    public static function categoryCount(Title $title, & $isInTopic) {
        $isInTopic = WikiTools::checkIfInTopicCategory($title);
    }

    public static function categoryViewerCategory(Title $title, & $html) {
        $html = '';
        if (WikiTools::checkIfInTopicCategory($title)) {
            $html = '<h2>Subtopics of "' . $title->getText(). '"</h2>';
        }
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

    public static function cleanupChemExtState() {

        RenderLiterature::$LITERATURE_REFS = [];
        MultiContentSave::$MOLECULES_FOUND = [];
        DOIRenderer::$PUBLICATIONS_FOUND = [];
        DOIInfoBox::$DOI_INFO_BOX = [];
    }

    /**
     * @param Parser $parser
     * @throws \MWException
     */
    private static function registerShowCachedHandler(Parser $parser): void
    {
        $applicationFactory = ServicesFactory::getInstance();
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

    /**
     * @param Title $wgTitle
     * @param OutputPage $out
     */
    private static function addSubtitle(OutputPage $out): void
    {
        $b = new NavigationBar($out->getTitle());
        if (NavigationBar::getCssType($out->getTitle()) === 'other') {
            return;
        }
        $link = self::createModifyLink();
        $investigationHints = self::createInvestigationHint($out);
        $out->addSubtitle('<div class="ce-subtitle-content">'
            . $b->getPageType()
            . $investigationHints
            .  self::$subTitleExtension
            . "$link</div>");
    }

    /**
     * @param OutputPage $out
     * @return string
     */
    private static function createInvestigationHint(OutputPage $out): string
    {
        $investigationHints = "";
        if (!is_null($out->getTitle()) && $out->getTitle()->isSubpage()) {
            $investigationHints = WikiTools::getInvestigationCategoriesAsString($out->getTitle());
        }
        return $investigationHints;
    }
}