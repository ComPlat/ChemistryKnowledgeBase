<?php

# use this file as a template for env.php (for local development)

#################################################################
#
# MW settings
#
#################################################################
global $wgDBname;       # make it global so that SOLR proxy can read
global $wgServer;       # make it global so that SOLR proxy can read
global $wgScriptPath;   # make it global so that SOLR proxy can read
global $wgServerHTTP;   # make it global so that SOLR proxy can read
$wgDBname     = "chemwiki";
$wgServer     = "http://chemwiki.local";
$wgScriptPath = "/mediawiki";
$wgServerHTTP = $wgServer;

$wgLogos = ['1x' => "$wgScriptPath/extensions/ChemExtension/resources/KIT-logo.png"];
$wgFavicon = "$wgScriptPath/extensions/ChemExtension/resources/favicon.ico";

$wgWikiServerPath   = __DIR__;


#################################################################
#
# SOLR
#
#################################################################
global $SOLRhost, $SOLRport, $SOLRcore, $SOLRuser, $SOLRpass;
$SOLRhost = 'localhost';
$SOLRport = 8983;
$SOLRcore = 'mw';

global $fsgActivateBoosting;
$fsgActivateBoosting=true;

global $SOLRProxyDebug;
$SOLRProxyDebug = true;


#################################################################
#
# Enable MW-logging
# see also https://www.semantic-mediawiki.org/wiki/Help:Identifying_bugs
#
#################################################################

# ERROR LOGGING
error_reporting(E_ALL);
error_reporting( -1 ); // catch all PHP errors
ini_set( 'display_errors', 1 ); // show the PHP errors caught
ini_set('session.gc_maxlifetime', 7200); // see bug #682

$date = (new DateTime())->format('Y-m-d');
$wgDebugLogFile = __DIR__ . "/logs/mw-debug_$date.log";
$wgShowExceptionDetails = true;
// $wgShowSQLErrors = true;
// $wgShowDBErrorBacktrace = false;

$wgSpecialVersionShowHooks = true;
$wgDebugComments = true;
