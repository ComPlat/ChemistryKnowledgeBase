<?php

namespace DIQA\WikiFarm;

use JetBrains\PhpStorm\NoReturn;
use MediaWiki\MediaWikiServices;

define('DIQA_WIKI_FARM', true);

class Setup
{

    public static function initModules(): void
    {
        global $wgResourceModules;
        global $IP;

        $scriptFolder = "/extensions/WikiFarm/scripts";
        $skinsFolder = "/extensions/WikiFarm/skins";

        $wgResourceModules['ext.diqa.wikifarm'] = array(
            'localBasePath' => "$IP",
            'remoteExtPath' => 'WikiFarm',
            'position' => 'bottom',
            'scripts' => [
                $scriptFolder . '/wf.special.createwiki.ajax.js',
                $scriptFolder . '/wf.special.createwiki.js',
            ],
            'styles' => [$skinsFolder . '/wf.special.createwiki.css'],
            'dependencies' => ['mediawiki.widgets.UserInputWidget', 'mediawiki.widgets.UsersMultiselectWidget', 'mediawiki.userSuggest'],
            "messages" => [
                "wfarm-ajax-error",
                "wfarm-remove-wiki-confirm"
            ],
        );
        self::setWikiName();

    }

    private static function setWikiName(): void
    {
        global $wgWikiFarmWikiId;
        global $wgSitename;
        if (is_null($wgWikiFarmWikiId)) {
            return;
        }
        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $wikiMetadata = (new WikiRepository($dbr))->getWikiById($wgWikiFarmWikiId);
        $wgSitename = $wikiMetadata[0]['wiki_name'] ?? '';
    }

    public static function onBeforePageDisplay(\OutputPage $out, \Skin $skin)
    {
        global $wgTitle;
        if (!is_null($wgTitle) && $wgTitle->getDBkey() == 'SpecialCreateWiki') {
            $out->addModules('ext.diqa.wikifarm');
        }
        self::checkPrivileges();
    }


    /**
     * Checks the privileges of a user.
     *
     */
    private static function checkPrivileges(): void
    {
        $callingURL = strtolower($_SERVER['REQUEST_URI']);
        $wikiId = self::parseWikiUrl($callingURL);
        if ($wikiId == "main") {
            return;
        }

        global $wgUser, $wgTitle;
        if ($wgUser->isAnon()) {
            if ($wgTitle->isSpecial("Userlogin")) {
                return;
            }
            self::redirectToLogin();
        }
        $wikiId = str_replace("wiki", "", $wikiId);

        $dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(
            DB_REPLICA
        );

        $mayAccess = (new WikiRepository($dbr))->mayAccess($wgUser, $wikiId);
        if (!$mayAccess) {
            self::accessDenied();
        }

    }

    private static function parseWikiUrl($url)
    {
        $matches = [];
        preg_match('/\/(\w+)\/mediawiki/', $url, $matches);
        return $matches[1] ?? NULL;
    }


    public static function isEnabled(): bool
    {
        return defined('DIQA_WIKI_FARM');
    }

    #[NoReturn]
    private static function redirectToLogin(): void
    {
        global $wgTitle, $wgServer, $wgScriptPath;
        header("Location: $wgServer$wgScriptPath/Special:Userlogin?returnto={$wgTitle->getPrefixedDBkey()}");
        die();
    }

    #[NoReturn]
    private static function accessDenied(): void
    {
        global $wgServer;
        print "Access denied. Go back to <a href=\"$wgServer/main/mediawiki\">main wiki</a>";
        die();
    }
}