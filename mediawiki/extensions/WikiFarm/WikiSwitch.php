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

global $wgSharedTables;
$wgSharedTables[] = 'wiki_farm';
$wgSharedTables[] = 'wiki_farm_user';

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

        // paths
        global $wgScriptPath;
        global $wgResourceBasePath;
        global $wgArticlePath;
        global $wgUsePathInfo;
        $wgUsePathInfo = true;
        $wgScriptPath = $this->getWikiPaths($wikiSelector);
        $wgResourceBasePath = $wgScriptPath;
        $wgArticlePath = $wgScriptPath . "/$1";
        $wikiRootWeb = "$wgScriptPath/env-farm-$wikiSelector";

        // DB
        global $wgDBname;
        $wgDBname = $this->getDBName($wikiSelector);

        // search
        $this->configureSearch($wikiSelector);

        // uploads
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

        global $wgWikiFarmForeignFileRepo;
        if (isset($wgWikiFarmForeignFileRepo)) {
            $this->configureForeignFileRepo($wgWikiFarmForeignFileRepo);
        }

        // get wiki ID from URL
        global $wgWikiFarmWikiId, $wgWikiFarmScriptWikiIdPattern;
        $pattern = $wgWikiFarmScriptWikiIdPattern ?? '{wiki}';
        $pattern = str_replace('{wiki}', '(\d+)', $pattern);
        $wgWikiFarmWikiId = preg_match("/$pattern/", $wikiSelector, $matches) ? $matches[1] : null;

        global $wgDebugLogFile;
        $date = (new DateTime())->format('Y-m-d');
        $wgDebugLogFile = "$wikiRootLocal/logs/mw-debug_$date.log";
    }

    private function configureForeignFileRepo($wikiSelector): void
    {
        global $wgSharedDB, $wgForeignFileRepos;
        $scriptPath = $this->getWikiPaths($wikiSelector);
        $wikiRootWeb = "$scriptPath/env-farm-$wikiSelector";
        $wgForeignFileRepos[] = [
            'class' => ForeignAPIRepo::class,
            'name' => $wgSharedDB,
            'apibase' => "$scriptPath/api.php",
            'url' => "$wikiRootWeb/images",
            'thumbUrl' => "$wikiRootWeb/images/thumb",
            'hashLevels' => 2,
        ];
    }

    private function configureSearch($wikiSelector): void
    {
        global $fs2gBackendConfig, $fs2gBackend, $fsgSolrCore;
        $fs2gBackend = 'solr';
        $fs2gBackendConfig = [
            'indexName' => $wikiSelector,
        ];
        $fsgSolrCore = $wikiSelector;
    }

    private function getDBName($wikiSelector): string {
        global $wgWikiFarmDBPatternMappings, $wgWikiFarmDBPattern;
        if (isset($wgWikiFarmDBPattern)) {
            $dbName = preg_replace('/\{wiki}/', $wikiSelector, $wgWikiFarmDBPattern);
        } else {
            $dbName = "{$wikiSelector}_wikidb";
        }
        if (array_key_exists($wikiSelector, $wgWikiFarmDBPatternMappings ?? [])) {
            $dbName = $wgWikiFarmDBPatternMappings[$wikiSelector];
        }
        return $dbName;
    }

    private function getWikiPaths($wikiSelector): string {
        global $wgUsePathInfo;
        $wgUsePathInfo = true;

        global  $wgWikiFarmScriptPathPattern;
        if (isset($wgWikiFarmScriptPathPattern)) {
            $scriptPath = preg_replace('/\{wiki}/', $wikiSelector, $wgWikiFarmScriptPathPattern);
        } else {
            $scriptPath = "/$wikiSelector";
        }
        return $scriptPath;
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
