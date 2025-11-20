<?php

namespace DIQA\FacetedSearch2;

use MediaWiki\MediaWikiServices;
use OutputPage;
use RequestContext;
use Skin;
use SMWDIProperty;

class Setup
{

    public static function initModules()
    {

        global $wgResourceModules;
        global $IP;

        self::checkIfCompiled();

        $basePath = "$IP/extensions/FacetedSearch2";
        $reactScript = "fs-react/public/main.js";

        $wgResourceModules['ext.diqa.facetedsearch2'] = array(
            'localBasePath' => $basePath,
            'remoteExtPath' => 'FacetedSearch2',
            'position' => 'bottom',
            'messages' => self::getMessageKeys(),
            'scripts' => [
                $reactScript,
            ],
            'styles' => ['fs-react/public/skins/main.css'],
            'dependencies' => ['mediawiki.user'],
        );

    }

    private static function getMessageKeys()
    {
        $keys = [];
        $messages = json_decode(file_get_contents(__DIR__ . '/../i18n/en.json'));
        foreach ($messages as $key => $value) {
            $keys[] = $key;
        }
        return $keys;
    }

    public static function setupFacetedSearch()
    {

        define('FS2_EXTENSION_VERSION', true);

        if (defined('ER_EXTENSION_VERSION')) {
            // if old version is installed in parallel, keep the it the standard search and ignore the FS2 setting
            global $wgSpecialPages;
            $wgSpecialPages['Search'] = "DIQA\\FacetedSearch\\Specials\\FSFacetedSearchSpecial";
        } else {
            global $fs2gFacetedSearchForMW;
            if (!$fs2gFacetedSearchForMW) {
                global $wgSpecialPages;
                unset($wgSpecialPages['Search']);
            }
        }

        global $fs2gEnableIncrementalIndexer;

        if ($fs2gEnableIncrementalIndexer) {
            $hookContainer = MediaWikiServices::getInstance()->getHookContainer();
            $hookContainer->register('SMW::SQLStore::AfterDataUpdateComplete', 'DIQA\FacetedSearch2\Update\FSIncrementalUpdater::onUpdateDataAfter');
            $hookContainer->register('UploadComplete','DIQA\FacetedSearch2\Update\FSIncrementalUpdater::onUploadComplete');
            $hookContainer->register('AfterImportPage','DIQA\FacetedSearch2\Update\FSIncrementalUpdater::onAfterImportPage');
            $hookContainer->register('PageMoveCompleting','DIQA\FacetedSearch2\Update\FSIncrementalUpdater::onTitleMoveComplete');
            $hookContainer->register('PageDelete','DIQA\FacetedSearch2\Update\FSIncrementalUpdater::onPageDelete');
            $hookContainer->register('ApprovedRevsRevisionApproved','DIQA\FacetedSearch2\Update\FSIncrementalUpdater::onRevisionApproved');
            $hookContainer->register('PageSaveComplete','DIQA\FacetedSearch2\Update\FSIncrementalUpdater::onPageSaveComplete');
        }
    }

    public static function initializeBeforeParserInit()
    {
        if (!RequestContext::getMain()->hasTitle()) {
            return true;
        }

        if (!self::isSpecialPageOrProxy()) {
            return true;
        }

        ConfigTools::initializeServersideConfig();
        $jsVars = self::readAllFS2ConfigVars();
        RequestContext::getMain()->getOutput()->addJsConfigVars($jsVars);

        return true;
    }

    private static function isSpecialPageOrProxy() {
        $currentTitle = RequestContext::getMain()->getTitle();
        $requestUrl = RequestContext::getMain()->getRequest()->getRequestURL();
        $isFacetedSearch2Page = !is_null($currentTitle)
            && $currentTitle->getNamespace() === NS_SPECIAL
            && ($currentTitle->getText() === 'FacetedSearch2' || $currentTitle->getText() === 'Search');
        $isProxyEndpoint = strpos($requestUrl, '/FacetedSearch2/v1/proxy') > -1;
        return $isFacetedSearch2Page || $isProxyEndpoint;
    }

    public static function onBeforePageDisplay(OutputPage $out, Skin $skin)
    {

        if (!is_null($out->getTitle())
            && ($out->getTitle()->isSpecial("FacetedSearch2") || $out->getTitle()->isSpecial("Search"))) {
            self::checkIfCompiled();
            $out->addModules('ext.diqa.facetedsearch2');
        }
    }

    private static function checkIfCompiled(): void
    {
        global $IP;
        if (!file_exists("$IP/extensions/FacetedSearch2/fs-react/public/main.js")) {
            trigger_error("You need to build FacetedSearch2. See README");
            die();
        }
    }

    /**
     * @return array
     */
    private static function readAllFS2ConfigVars(): array
    {
        $jsVars = [];
        foreach ($GLOBALS as $var => $value) {
            if (strpos($var, 'fs2g') === 0) {
                $jsVars[$var] = $value;
            }
        }
        return $jsVars;
    }


}