<?php
/**
 * This implementation switches between different databases / URL paths / image folders
 *
 * Erzeugung eines neuen Wikis verlangt:
 *
 *  - eine leere DB importieren (mit Wiki-Schema),
 *  - ein leeres Bilderverzeichnis anlegen
 *  - ein symlink angelegen
 *  - eine kleine Infodatei mit Metadaten (Name des Wikis z.b.) schreiben
 *  - ein Eintrag in eine neue Tabelle schreiben, die den Benutzer als Eigentümer des neuen Wikis ausweist
 *  - einen neuen leeren SOLR core erzeugen
 *  - und ein Eintrag in die interwiki-Tabelle schreiben.
 */
$wgSharedDB="chemwiki";
$wgSharedTables = [ 'user', 'user_properties'];
$callingurl = strtolower( $_SERVER['REQUEST_URI'] ); // get the calling url
if ( strpos( $callingurl, '/mediawiki' )  === 0 ) {
    $wgDBname = "chemwiki";
    $wgScriptPath = "/mediawiki";
    $wgArticlePath = '/mediawiki/$1';
    $wgUploadDirectory = "/var/www/html/images1";
    $wgUploadPath = "/images1";
} elseif ( strpos( $callingurl, '/wiki2' ) === 0 ) {
    $wgDBname = "chemwiki2";
    $wgScriptPath = "/wiki2";
    $wgArticlePath = '/wiki2/$1';
    $wgUploadDirectory = "/var/www/html/images2";
    $wgUploadPath = "/images2";

    /*$wgForeignFileRepos[] = [
        'class' => FileRepo::class,
        'name' => 'sharedFsRepo',
        'directory' => "/var/www/html/images1",
        'hashLevels' => 2,
        'url' => 'http://localhost/images1',
    ];*/
    $wgForeignFileRepos[] = [
        'class' => ForeignAPIRepo::class,
        'name' => 'chemwiki',
        'apibase' => 'http://localhost/mediawiki/api.php',
        'url' => 'http://localhost/images1',
        'thumbUrl' => 'http://localhost/images1/thumb',
        'hashLevels' => 2,
    ];

} else {
    header( 'HTTP/1.1 404 Not Found' );
    echo "This wiki (\"" . htmlspecialchars( $callingurl ) . "\") is not available. Check configuration.";
    exit( 0 );
}