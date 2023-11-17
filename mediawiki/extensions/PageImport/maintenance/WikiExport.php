<?php
// Usage:
//
//     php WikiExport.php [OPTIONS]
//
// Options:
//     --namespace=NAMESPACE
//         Select a list of namesapace (comma-separated).
//         If no namespace is selected, all namespaces are selected.
//
//     --directory=DIRECTORY
//         Select the directory containing the folders for each namespace.
//         If no directory is selected, the current directory is used.
//

use DIQA\Formatter\Color;
use DIQA\Formatter\Config;
use DIQA\Formatter\Formatter;
use DIQA\PageImport\LoggerUtils;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

class WikiExport extends Maintenance {

    const CREATED  = "[ created  ]";
    const MOVED    = "[  moved   ]";
    const DELETED  = "[ deleted  ]";
    const ERROR    = "[  error   ]";

    /// File extension of wiki files
    const FILE_EXTENSION = 'wiki';

    // Access rights for files.
    // read = 4, write = 2, execute = 1
    const DIRECTORY_MODE = 0766;

    // Defaul folder for deleted pages
    const DELETED_FOLDER = ".deleted";

    // folder for files
    private $StorageDirectory = "";

    //folder for deleted files
    private $DeletedDirectory = self::DELETED_FOLDER;

    // Contains all Namespaces.
    private $Namespaces = null;

    /// Array of namespaces that should be exported
    private $SelectedNamespaces = null;

    private $formatter;
    private $logger;

    public function __construct() {
        parent::__construct();


        $this->addDescription( 'Script for exporting wiki pages to readable individual files.' );
        $this->addOption( 'directory', 'files will be exported to subfolder of this diretory', false, true );
        $this->addOption( 'namespace', 'Only show/count jobs of a given type', false, true );
    }

    public function execute() {
        // Get all namespaces.
        $this->Namespaces = $this->getNamespaces();

        $this->logger = new LoggerUtils('WikiExporter', 'PageImport', "OFF", false);
        $this->initFormatter();
        $this->processOptions();
        $this->runWikiExport();
    }

    private function processOptions() {
        // process and create "directory"
        $directory = $this->getOption( 'directory', null);
        if ($directory != null) {

            if (substr ( $directory, 0, 1 ) == "/") {
                $this->StorageDirectory = $directory;
            } else {
                $this->StorageDirectory = getcwd () . "/" . $directory;
            }

            if(substr($this->StorageDirectory, -1) == "/") {
                $this->StorageDirectory = substr($this->StorageDirectory, 0, -1);
            }

            $this->createDirectory( $this->StorageDirectory );
        } else {
            $this->StorageDirectory = getcwd ();
        }

        // create directory for deleted pages
        $this->DeletedDirectory = $this->StorageDirectory . "/" . self::DELETED_FOLDER;
        $this->createDirectory($this->DeletedDirectory);


        // process and create "namespace"
        $namespaces = $this->getOption( 'namespace', null);
        if ($namespaces != null) {
            $this->SelectedNamespaces = explode ( ",", $namespaces );
            foreach ($this->SelectedNamespaces as $selectedNamespace) {
                if (!in_array($selectedNamespace, $this->Namespaces)) {
                    die ( "Unknown namespace: " . $selectedNamespace . "\n" );
                }
            }
        } else {
            $this->SelectedNamespaces = $this->Namespaces;
        }
    }


    /**
     * Create Directories and Files.
     *
     * @return void
     */
    private function runWikiExport() {
        $this->printLine("Exporting pages...\n");
        foreach ($this->SelectedNamespaces as $namespace) {
            $this->exportPagesForNamespace($namespace);
        }
        $this->printLine("\n");
    }

    private function exportPagesForNamespace($namespace) {
        $namespaceId = array_search ( $namespace, $this->Namespaces );
        if ($namespaceId === false) {
            $this->printLine( $namespace . ": no pages\n");
        } else {
            $pageTitles = $this->getPageTitles( $namespaceId );
            $this->printLine( $namespace . ": " . count($pageTitles) . " pages\n");
            foreach ($pageTitles as $pageTitle) {
                $this->exportPage($pageTitle, $namespaceId, $namespace);
            }

            $this->handleDeletedPages( $namespace, $pageTitles );
        }
    }

    private function exportPage($pageTitle, $namespaceId, $namespaceName) {
        $directory = $this->StorageDirectory . "/" . $namespaceName;
        $this->createDirectory($directory);

        $filePath = $directory . "/" . $this->encodeFileName ( $pageTitle );

        if ($namespaceId == 0) {
            $wikiText = $this->getWikiText ( $pageTitle );
        } else {
            $wikiText = $this->getWikiText ( $namespaceName . ":" . $pageTitle );
        }

        $this->printLine( $this->formatter->formatLine( $pageTitle, $filePath, self::CREATED));
        file_put_contents ( $filePath, $wikiText );

        $this->handleRestoredPage ( $namespaceName, $pageTitle );
    }

    /**
     * Get all Namespaces.
     *
     * $Namespaces = [[0] => "Namespace_1",
     * [1] => "Namespace_2",
     * [2] => "Namespace_3"]
     *
     * @param void
     * @return array List of all namespaces.
     */
    private function getNamespaces() {
        $Namespaces = RequestContext::getMain()->getLanguage()->getNamespaces();
        $Namespaces[0] = 'Main';
        return $Namespaces;
    }

    /**
     * Get all page titles belonging to a given namespace id
     * from the database table "page".
     *
     * @param integer $NamespaceId Namespace id of the namespace.
     * @return array List of all page titles.
     */
    private function getPageTitles($namespaceId) {
        $pageTitles = array();
        if (! is_null ( $namespaceId )) {
            $dbConnection = wfGetDB ( DB_REPLICA );
            $result = $dbConnection->select ( array ("page"),
                array (	"page_id", "page_title", "page_namespace"),
                "page_namespace = " . $namespaceId,
                __METHOD__,
                array () );
            if ($result->numRows () > 0) {
                foreach ( $result as $row ) {
                    $pageTitles [] = $row->page_title;
                }
            }
        }

        return $pageTitles;
    }

    /**
     * Get the wiki markup of a page from database table "page".
     *
     * @param String $PageTitle The title of the page
     * @return String The wiki markup of the page.
     */
    private function getWikiText($pageTitle) {
        $wikiMarkup = null;

        if (! is_null ( $pageTitle )) {
            $title = \Title::newFromText ( $pageTitle );
            $revision = MediaWikiServices::getInstance()->getRevisionLookup()->getRevisionByTitle( $title );
            if (! is_null ( $revision )) {
                $revisionContent = $revision->getContent( SlotRecord::MAIN );
                if (! is_null ( $revisionContent )) {
                    $wikiMarkup = $revisionContent->serialize (); // or: $WikiMarkup = WikiPage::getContent(...)->serialize();
                }
            }
        }

        return $wikiMarkup;
    }

    /**
     * Encode the filename and add ".wiki" to it
     *
     * @param String $FileName The filename.
     * @return String The encoded filename.
     */
    private function encodeFileName($fileName) {
        return urlencode ( $fileName )  . "." . self::FILE_EXTENSION;
    }

    /**
     * Decode the filename.
     *
     * @param String $Filename The decoded filename.
     * @return String The filename.
     */
    private function decodeFileName($fileName) {
        return urldecode ( $fileName );
    }

    /**
     * If the page is restored and then the pages will be exported again,
     * then the corresponding file is deleted in $DeletedDirectory.
     *
     * @param $NamespaceName The namespace.
     * @param $PageTitle     The page title.
     * @return void
     */
    private function handleRestoredPage($namespaceName, $pageTitle) {
        $file = $this->DeletedDirectory . "/" . $namespaceName . "/" . $this->encodeFileName ( $pageTitle ) ;
        if (is_file ( $file )) {
            $this->printLine( $this->formatter->formatLine($file, "", self::DELETED));
            unlink ( $file );
        }
    }

    /**
     * If a wiki page does not exist anymore but the corresponding file
     * exists in the export folder, then it is moved to the $DeletedDirectory.
     *
     * @param $NamespaceName The current namespace.
     * @param $PageTitles    The list of pages that DO exist in the namespace.
     * @return void
     */
    private function handleDeletedPages($namespaceName, $pageTitles) {
        $pagesDir = $this->StorageDirectory . "/" . $namespaceName ;
        $archiveDir = $this->DeletedDirectory . "/" . $namespaceName;

        if (! file_exists ( $pagesDir )) {
            return;
        }

        $existingFiles = scandir ( $pagesDir );

        foreach ( $pageTitles as $pageTitle ) {
            if (in_array ( $this->encodeFileName ( $pageTitle ), $existingFiles )) {
                unset ( $existingFiles [array_search ( $this->encodeFileName ( $pageTitle ), $existingFiles )] );
            }
        }

        // all files that are still in $existingFiles are NOT present in the wiki anymore and must be moved to .deleted
        foreach ( $existingFiles as $existingFile ) {
            $pagesFile = $pagesDir . "/" . $existingFile;
            $archiveFile = $archiveDir . "/" . $existingFile;

            if (is_file ( $pagesFile )) {
                $this->createDirectory($archiveDir);
                $this->printLine( $this->formatter->formatLine($pagesFile,  $archiveFile, self::MOVED));
                copy ( $pagesFile, $archiveFile );
                unlink ( $pagesFile );
            }
        }
    }

    private function createDirectory($dirName) {
        if (! file_exists ( $dirName )) {
            $this->printLine( "Creating directory " . $dirName . "\n");
            mkdir ( $dirName, self::DIRECTORY_MODE, true );
        }
    }

    private function initFormatter(): void
    {
        $config = new Config([60, 80, 20], [Config::LEFT_ALIGN, Config::LEFT_ALIGN, Config::LEFT_ALIGN]);
        $config->highlightWord(self::CREATED, Color::fromColor(COLOR::BLACK, Color::GREEN), 2);
        $config->highlightWord(self::ERROR, Color::fromColor(COLOR::BLACK, Color::RED), 2);
        $config->highlightWord(self::DELETED, Color::fromColor(COLOR::BLACK, Color::MAGENTA), 2);
        $config->highlightWord(self::MOVED, Color::fromColor(COLOR::BLACK, Color::YELLOW), 2);
        $config->setLeftColumnPadding(0,3);
        $config->setLeftColumnPadding(1,2);
        $config->setLeftColumnPadding(2,1);
        $this->formatter = new Formatter($config);
    }

    private function printLine($line, $lvl = 'log') {
        $trimmedLine = trim($this->removeEscapeSeqs($line));
        if ($trimmedLine !== '') {
            $this->logger->{$lvl}($trimmedLine);
        }
        echo "\n$line";
    }

    private function removeEscapeSeqs($s) {
        return preg_replace('/\033\[[^]]+m/', '', $s);
    }

} // end of class WikiExport

$maintClass = 'WikiExport';
require_once RUN_MAINTENANCE_IF_MAIN;
