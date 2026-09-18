<?php

/**
 * This implementation switches between different Wiki instances
 * i.e. databases / URL paths / image folders / SOLR cores
 */

// Protect against web entry
if (!defined('MEDIAWIKI')) {
    exit;
}
global $IP;

$solrCoreTemplate = getenv('WIKI_SOLR_TEMPLATE') ? getenv('WIKI_SOLR_TEMPLATE') : "$IP/extensions/WikiFarm/resources/mw";

$filename = WikiSwitch::start();
if ($filename) {
    require_once($filename);
}

class WikiSwitch
{
    /**
     * @return string $filename of the env-file that must be used
     */
    public static function start(): string
    {
        $worker = new WikiSwitch();
        return $worker->switchWiki();
    }

    /**
     * @return string $filename of the env-file that must be used
     */
    private function switchWiki(): string
    {
        global $wgWikiFarmAllowMissingEnv;
        $wikiSelector = $this->identifyWiki();
        $this->configureWiki($wikiSelector);
        global $IP;
        $filename = "$IP/env-farm-$wikiSelector/env.php";
        if (file_exists($filename)) {
            return $filename;
        } else {
            if (isset($wgWikiFarmAllowMissingEnv) && $wgWikiFarmAllowMissingEnv === true) {
                return '';
            }
            $this->error('This wiki (' . htmlspecialchars($wikiSelector) . ') is not configured (properly).', 404);
            return '';
        }
    }

    private function identifyWiki(): string
    {
        if (!isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI'] == '' || php_sapi_name() == 'cli') {
            // CLI mode, read env
            global $wgWikiFarmDefaultWikiId;
            $wikiSelector = strtolower(getenv('WIKI') ? getenv('WIKI') : $wgWikiFarmDefaultWikiId);
            print "\nWikiSwitch: Wiki is set to '$wikiSelector' (env WIKI)\n\n";
            $callingUrl = 'cli';
        } else {
            $callingUrl = strtolower($_SERVER['REQUEST_URI']);
            $wikiSelector = $this->parseWikiUrl($callingUrl);
        }

        if (is_null($wikiSelector) || $wikiSelector === '' || $wikiSelector === false) {
            $this->error('This wiki (' . htmlspecialchars($callingUrl) . ') is not available.', 404);
        }

        return $wikiSelector;
    }

    private function parseWikiUrl($url)
    {
        $matches = [];
        preg_match('/\/(\w+)\//', $url, $matches);
        return $matches[1] ?? NULL;
    }

    /**
     * Configures each Wiki in the farm with canonical values.
     * They can be overwritten in the specific env.php files.
     */
    private function configureWiki($wikiSelector)
    {
        global $IP;
        $wikiRootLocal = "$IP/env-farm-$wikiSelector";
        $wikiRootWeb = "/$wikiSelector/env-farm-$wikiSelector";

        global $wgScriptPath;
        global $wgResourceBasePath;
        global $wgDBname;
        global $wgSitename;
        global $wgArticlePath;

        global $fs2gBackendConfig, $fs2gBackend, $fsgSolrCore;
        $fs2gBackend = 'solr';
        global $wgWikiFarmDBPatternMappings, $wgWikiFarmDBPattern, $wgWikiFarmScriptPathPattern;
        if (isset($wgWikiFarmScriptPathPattern)) {
            $wgScriptPath = preg_replace('/\{wiki}/', $wikiSelector, $wgWikiFarmScriptPathPattern);
            $wgResourceBasePath = $wgScriptPath;
            $wgArticlePath = $wgScriptPath . "/$1";
        } else {
            $wgScriptPath = "/$wikiSelector";
            $wgResourceBasePath = "/$wikiSelector";
        }
        if (isset($wgWikiFarmDBPattern)) {
            $wgDBname = preg_replace('/\{wiki}/', $wikiSelector, $wgWikiFarmDBPattern);
        } else {
            $wgDBname = "{$wikiSelector}_wikidb";
        }
        if (array_key_exists($wikiSelector, $wgWikiFarmDBPatternMappings ?? [])) {
            $wgDBname = $wgWikiFarmDBPatternMappings[$wikiSelector];
        }
        $wgSitename = $wikiSelector;
        $fs2gBackendConfig = [
            'indexName' => $wikiSelector,
        ];
        $fsgSolrCore = $wikiSelector;

        global $wgCacheDirectory;
        global $wgFileCacheDirectory;
        $wgCacheDirectory = "$wikiRootLocal/cache";
        $wgFileCacheDirectory = "$wikiRootLocal/cache";

        global $wgUploadDirectory;
        global $wgUploadPath;
        global $wgFavicon;
        $wgUploadDirectory = "$wikiRootLocal/images";
        $wgUploadPath = "$wikiRootWeb/images";
        $wgFavicon = "$wikiRootWeb/favicon.ico";

        // see https://www.mediawiki.org/wiki/Manual:$wgLogos
        global $wgLogos;
        $logo = $this->findImage($wikiRootLocal, 'logo');
        if ($logo) {
            $wgLogos['icon'] = "$wikiRootWeb/$logo";
        }
        $wordMark = $this->findImage($wikiRootLocal, 'wordmark');
        if ($wordMark) {
            $wgLogos['wordmark'] = ['src' => "$wikiRootWeb/$wordMark", 'width' => 124, 'height' => 32];
        }
        // there seems to be no tagline support in Timeless, yet
        $tagLine = $this->findImage($wikiRootLocal, 'tagline');
        if ($tagLine) {
            $wgLogos['tagline'] = ['src' => "$wikiRootWeb/$tagLine", 'width' => 124, 'height' => 18];
        }

        global $wgDebugLogFile;
        $date = (new DateTime())->format('Y-m-d');
        $wgDebugLogFile = "$wikiRootLocal/logs/mw-debug_$date.log";
    }

    private function findImage(string $wikiRootLocal, string $baseName): string
    {
        if (file_exists("$wikiRootLocal/$baseName.svg")) {
            return "$baseName.svg";
        } else if (file_exists("$wikiRootLocal/$baseName.png")) {
            return "$baseName.png";
        } else {
            return '';
        }
    }

    private function error($message, $responseCode = 500)
    {
        http_response_code($responseCode);
        echo("<h1>WikiSwitch Error</h1><p>$message</p>\n");
        exit(0);
    }
}
