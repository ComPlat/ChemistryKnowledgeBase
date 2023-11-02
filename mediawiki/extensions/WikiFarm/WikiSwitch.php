<?php
/**
 * This implementation switches between different databases / URL paths / image folders
 *
 * Erzeugung eines neuen Wikis verlangt:
 *
 *  - eine leere DB importieren (mit Wiki-Schema),
 *  - ein Verzeichnis mit der wikiId unter /var/www/html anlegen mit folgendem Inhalt:
 *      - ein symlink mit wikiId auf mediawiki folder
 *      - eine kleine Infodatei mit Metadaten (Name des Wikis z.b.) schreiben. Name: $wikiId.json
 *      - ein leeres Image Verzeichnis (images) mit Schreibrechten für Apache
 *  - ein Eintrag in eine neue Tabelle schreiben, die den Benutzer als Eigentümer des neuen Wikis ausweist
 *  - einen neuen leeren SOLR core erzeugen
 *  - und ein Eintrag in die interwiki-Tabelle schreiben ?? (nur wenn sich Wikis gegenseitig referenzieren)
 */
$wgSharedDB = "chemmain_139";
$wgSharedTables[] = 'wiki_farm';
$wgSharedTables[] = 'wiki_farm_user';
if (!isset($_SERVER['REQUEST_URI'])) {
    $wiki = "main";
} else {
    $callingurl = strtolower($_SERVER['REQUEST_URI']);
    $wiki = parseWikiUrl($callingurl);
}

if (is_null($wiki)) {
    header('HTTP/1.1 404 Not Found');
    echo "This wiki (\"" . htmlspecialchars($callingurl) . "\") is not available. Check configuration.\n";
    exit(0);
}

global $fsgSolrCore;
if ($wiki == 'main') {
    $wgDBname = "chemmain_139";
    $wgScriptPath = "/main/mediawiki";
    $wgArticlePath = '/main/mediawiki/$1';
    $wgUploadDirectory = "/var/www/html/main/images";
    $wgUploadPath = "/main/images";
    $fsgSolrCore = 'main';
} else {
    $wgDBname = "chem$wiki";
    $wgScriptPath = "/$wiki/mediawiki";
    $wgArticlePath = "/$wiki/mediawiki/$1";
    $wgUploadDirectory = "/var/www/html/$wiki/images";
    $wgUploadPath = "/$wiki/images";
    $fsgSolrCore = $wiki;

    global $wgServer;
    $wgForeignFileRepos[] = [
        'class' => ForeignAPIRepo::class,
        'name' => 'chemmain',
        'apibase' => "$wgServer/main/mediawiki/api.php",
        'url' => "$wgServer/main/images",
        'thumbUrl' => "$wgServer/main/images/thumb",
        'hashLevels' => 2,
    ];
}
$wgResourceBasePath = $wgScriptPath;

global $wgSitename;
$metadata = parseWikiMetadata($wiki);
$wgSitename = $metadata["name"];


function parseWikiUrl($url) {
    $matches = [];
    preg_match('/\/(\w+)\/mediawiki/', $url, $matches);
    return $matches[1] ?? NULL;
}

function parseWikiMetadata($wiki) {
    $result = [];
    $result["name"] = "Personal ChemWiki";
    $filePath = "/var/www/html/$wiki/$wiki.json";
    if (!file_exists($filePath)) {
        return $result;
    }
    $json = json_decode(file_get_contents($filePath));
    if (isset($json->name)) {
        $result["name"] = $json->name;
    }
    return $result;
}