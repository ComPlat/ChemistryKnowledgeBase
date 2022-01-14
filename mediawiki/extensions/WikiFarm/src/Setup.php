<?php
namespace DIQA\WikiFarm;

use MediaWiki\MediaWikiServices;

define('DIQA_WIKI_FARM', true);

class Setup {

    public static function initModules() {
        global $wgResourceModules;
        global $IP;

        $scriptFolder = "/extensions/WikiFarm/scripts";
        $skinsFolder = "/extensions/WikiFarm/skins";

        $wgResourceModules['ext.diqa.wikifarm'] = array(
            'localBasePath' => "$IP",
            'remoteExtPath' => 'WikiFarm',
            'position' => 'bottom',
            'scripts' => [
                $scriptFolder .'/wf.special.createwiki.ajax.js',
                $scriptFolder .'/wf.special.createwiki.js',
            ],
            'styles' => [ $skinsFolder . '/wf.special.createwiki.css'],
            'dependencies' => ['mediawiki.widgets.UserInputWidget', 'mediawiki.widgets.UsersMultiselectWidget', 'mediawiki.userSuggest'],
            "messages" => [
                "wfarm-ajax-error",
                "wfarm-remove-wiki-confirm"
            ],
        );
    }

    public static function onBeforePageDisplay( \OutputPage $out, \Skin $skin ) {
        $out->addModules('ext.diqa.wikifarm');
        self::checkPrivileges();
    }


    /**
     * Checks the privileges of a user.
     *
     */
    private static function checkPrivileges()
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
            self::accessDeniedBecauseUserAnon();
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

    private static function parseWikiUrl($url) {
        $matches = [];
        preg_match('/\/(\w+)\/mediawiki/', $url, $matches);
        return $matches[1] ?? NULL;
    }


    public static function isEnabled()
    {
        return defined('DIQA_WIKI_FARM');
    }

    private static function accessDeniedBecauseUserAnon()
    {
        global $wgServer,$wgScriptPath;
        header("Location: $wgServer$wgScriptPath/Special:Userlogin");
        die();
    }

    private static function accessDenied()
    {
        global $wgServer;
        print "Access denied. Go back to <a href=\"$wgServer/main/mediawiki\">main wiki</a>";
        die();
    }
}