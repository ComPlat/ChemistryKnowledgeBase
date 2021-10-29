<?php
namespace DIQA\WikiFarm;

use MediaWiki\MediaWikiServices;

define('DIQA_WIKI_FARM', true);

class Setup {

    public static function initModules() {
        global $wgResourceModules;
        global $IP;

        $scriptFolder = "/extensions/WikiFarm/scripts";

        $wgResourceModules['ext.diqa.wikifarm'] = array(
            'localBasePath' => "$IP",
            'remoteExtPath' => 'WikiFarm',
            'position' => 'bottom',
            'scripts' => [ $scriptFolder .'/wf.special.craetewiki.js'],
            'styles' => [],
            'dependencies' => [],
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
            self::accessDenied();
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

    private static function accessDenied()
    {
        print "Access denied";
        die();
    }
}