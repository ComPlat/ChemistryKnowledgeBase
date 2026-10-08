<?php

namespace DIQA\FacetedSearch2;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use OutputPage;
use RequestContext;
use Skin;

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

    private static function getMessagesFromSMW(): array {
        if (!class_exists('\SMW\Localizer\Localizer')) {
            return [];
        }
        $lang = \SMW\Localizer\Localizer::getInstance()->getLang();
        return $lang->getPropertyLabels();
    }

    public static function setupFacetedSearch()
    {

        define('FS2_EXTENSION_VERSION', true);

        global $fsgFacetedSearchForMW;
        if (defined('ER_EXTENSION_VERSION') && ($fsgFacetedSearchForMW ?? true)) {
            // if old version is installed in parallel, keep the it the standard search and ignore the FS2 setting
            global $wgSpecialPages;
            $wgSpecialPages['Search'] = "DIQA\\FacetedSearch\\Specials\\FSFacetedSearchSpecial";
        } else {
            global $fs2gFacetedSearchForMW;
            if (!($fs2gFacetedSearchForMW ?? true)) {
                global $wgSpecialPages;
                unset($wgSpecialPages['Search']);
            }
        }

        global $fs2gEnableIncrementalIndexer;

        if ($fs2gEnableIncrementalIndexer) {
            $hookContainer = MediaWikiServices::getInstance()->getHookContainer();
            $hookContainer->register('SMW::SQLStore::AfterDataUpdateComplete', 'DIQA\FacetedSearch2\Update\Hooks::onUpdateDataAfter');
            $hookContainer->register('UploadComplete','DIQA\FacetedSearch2\Update\Hooks::onUploadComplete');
            $hookContainer->register('AfterImportPage','DIQA\FacetedSearch2\Update\Hooks::onAfterImportPage');
            $hookContainer->register('PageMoveCompleting','DIQA\FacetedSearch2\Update\Hooks::onTitleMoveComplete');
            $hookContainer->register('PageDelete','DIQA\FacetedSearch2\Update\Hooks::onPageDelete');
            $hookContainer->register('ApprovedRevsRevisionApproved','DIQA\FacetedSearch2\Update\Hooks::onRevisionApproved');
            $hookContainer->register('PageSaveComplete','DIQA\FacetedSearch2\Update\Hooks::onPageSaveComplete');
        }
    }

    public static function initializeBeforeParserInit()
    {
        if (PHP_SAPI == 'cli') {
            return true;
        }

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

    private static function isSpecialPageOrProxy(): bool
    {

        $currentTitle = RequestContext::getMain()->getTitle();
        $requestUrl = RequestContext::getMain()->getRequest()->getRequestURL();
        $isFacetedSearch2Page = !is_null($currentTitle)
            && ($currentTitle->isSpecial('FacetedSearch2')
                || self::shouldReplaceDefaultSearch($currentTitle)
            );
        $isProxyEndpoint = str_contains($requestUrl, '/FacetedSearch2/v1/proxy');
        return $isFacetedSearch2Page || $isProxyEndpoint;
    }

    public static function onBeforePageDisplay(OutputPage $out, Skin $skin)
    {
        if (!is_null($out->getTitle())
            && ($out->getTitle()->isSpecial("FacetedSearch2")
                || self::shouldReplaceDefaultSearch($out->getTitle()))
        ) {
            self::checkIfCompiled();
            $out->addModules('ext.diqa.facetedsearch2');
            $out->addJsConfigVars('fs2gSMWLanguage', self::getMessagesFromSMW() );
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

    private static function shouldReplaceDefaultSearch(Title $title): bool
    {
        global $fs2gFacetedSearchForMW;
        return $title->isSpecial('Search') && ($fs2gFacetedSearchForMW ?? true);
    }


}