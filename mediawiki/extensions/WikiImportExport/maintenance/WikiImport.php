<?php
// Usage:
//
//     php WikiImport.php [OPTIONS]
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
// Note: Convert line endings of text files to unix line endings.

namespace WikiImportExport;

use \Maintenance;
use \ContentHandler;
use MediaWiki\MediaWikiServices;
use \WikiPage;
use \Revision;
use \Title;
use \RequestContext;
use MediaWiki\Storage\RevisionRecord;
error_reporting(E_ERROR);

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

class WikiImport extends Maintenance {

    /// File extension of wiki files
    const FILE_EXTENSION = 'wiki';

    /// Subject for creating or updating pages
    const SUBJECT = 'Added to the wiki via WikiImport-Script.';

    /// Array of namespaces that should be imported
    private $selectedNamespaces = null;

    /// Array of all namespaces in the Wiki
    private $allNamespaces = null;

    /// Storage directory.
    private $storageDirectory = null;

    /// show verbose log output
    private $verbose = false;

    public function __construct() {
        parent::__construct();

        $this->addDescription( 'Script for exporting wiki pages to readable individual files.' );
        $this->addOption( 'directory', 'files will be exported to subfolder of this diretory', false, true );
        $this->addOption( 'namespace', 'Only show/count jobs of a given type', false, true );
        $this->addOption( 'verbose', 'Show more details in the log output', false, false, 'v' );
    }

    public function execute() {


        // Get all namespaces.
        $this->allNamespaces = $this->getNamespaces();
        $this->processOptions();
        $this->doWikiImport();
        echo "\n";
    }

    /**
     * parse the commandline options amd populate $this->selectedNamespaces and $this->storageDirectory
     * @return void
     */
    private function processOptions() {
        global $wgODBLogLevel;
        if($this->hasOption('verbose')) {
            $wgODBLogLevel = 'DEBUG';
        } else {
            $wgODBLogLevel = 'LOG';
        }

        $namespaces = $this->getOption( 'namespace', null);
        if($namespaces!= null) {
            $this->selectedNamespaces = explode(',', $namespaces);
        } else {
            $this->selectedNamespaces = null;
        }

        $directory = $this->getOption( 'directory', null);
        if ($directory != null) {
            $this->storageDirectory = $directory;
        } else {
            $this->storageDirectory = getcwd();
        }
    }

    /**
     * The main method.
     * @return void
     */
    private function doWikiImport() {
        $relevantNamespaces = $this->getRelevantNamespaces ( $this->selectedNamespaces, $this->allNamespaces );

        echo "\nImporting files...";

        foreach ( $relevantNamespaces as $nsID => $nsName ) {
            echo "\n$nsName";

            $this->importNamespace ( $nsID, $nsName );
            $this->checkMissingPages ( $nsID, $nsName );
        }
    }

    /**
     * import the files for this single namespace.
     * @return void
     */
    private function importNamespace( $nsID, $nsName ) {
        $namespaceDir = $this->storageDirectory . '/' . $nsName;
        if (is_dir ( $namespaceDir )) {
            $allFiles = scandir($namespaceDir);
            foreach ($allFiles as $file) {
                if (is_dir("$namespaceDir/$file")) {
                    continue;
                }

                $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                if ($fileExtension == self::FILE_EXTENSION) {
                    $this->importFile($nsID, $nsName, "$namespaceDir/$file");
                }
                if ($nsID === NS_FILE) {
                    $imageDir = $this->storageDirectory . '/images';
                    $imageFilenameInWiki = pathinfo($file, PATHINFO_FILENAME);
                    $title = \Title::newFromDBkey( "File:".urldecode($imageFilenameInWiki));
                    $this->uploadImage("$imageDir/$imageFilenameInWiki", $title, "");
                }
            }
        } else {
            echo "\n\tno files.";
        }
    }

    /**
     * import a single file.
     * @return void
     */
    private function importFile($nsID, $nsName, $file) {
        $encodedFileName = pathinfo($file, PATHINFO_FILENAME);

        if (empty($encodedFileName)) {
            echo "\n\tignoring $file -- It has no title.";
            return;
        }

        $decodedFileName = urldecode($encodedFileName);

        if ($nsID == 0) {
            $pageTitle = Title::newFromText($decodedFileName);
        } else {
            $pageTitle = Title::newFromText("$nsName:$decodedFileName");
        }

        $wikiPage = new WikiPage($pageTitle);
        $newContentString = file_get_contents($file);
        $newContentString = trim($newContentString);
        $newContentObject = ContentHandler::makeContent($newContentString, $pageTitle);

        // If the page already exists compare the page content and file content.
        if ($wikiPage->exists()) {
            $revision = Revision::newFromTitle($pageTitle);
            $oldContentString = $revision->getContent(RevisionRecord::RAW)->serialize();
            // or: $WikiMarkup = WikiPage::getContent(...)->serialize();
            $oldContentString = trim($oldContentString);

            if ($oldContentString == $newContentString) {
                echo("\n\tignoring $decodedFileName -- It has same content as wiki page.");
                return;

            } else {
                $result = $wikiPage->doEditContent($newContentObject, self::SUBJECT, EDIT_UPDATE);
                if ($result->isOK()) {
                    echo("\n\tupdating $decodedFileName");
                } else {
                    echo("\n\tupdating $decodedFileName <- Error!!!");
                }
            }

        } else     {
            // New page.
            $result = $wikiPage->doEditContent($newContentObject, self::SUBJECT, EDIT_NEW);
            if ($result->isOK()) {
                echo("\n\tcreating $decodedFileName");
            } else {
                echo("\n\tcreating $decodedFileName <- Error!!!");
            }
        }

    }

    private function uploadImage($imageFilePath, Title $filePageTitle, $wikitext, $comment = "DIQA-Tool hat Datei hochgeladen.")
    {

        $services = MediaWikiServices::getInstance();
        $lbf = $services->getDBLoadBalancerFactory();

        try {
            if (!file_exists($imageFilePath)) {
                echo "\n\tFile does not exist: $imageFilePath. Skip it";
                return;
            }
            echo "\n\tImage file: $imageFilePath";
            echo "\n\tUploading image file into {$filePageTitle->getPrefixedDBkey()}. ";
            $filePage = wfLocalFile($filePageTitle);

            $lbf->beginMasterChanges(__METHOD__);
            $uploadStatus = $filePage->upload($imageFilePath, $comment, $wikitext);
            $lbf->commitMasterChanges(__METHOD__);

            if ($uploadStatus->isOk()) {
                echo "\n\tFile '{$filePageTitle->getPrefixedText()}' uploaded.";
            } else {
                $errors = $uploadStatus->getErrorsArray();
                if (isset($errors[0][0]) && $errors[0][0] == 'fileexists-no-change') {
                    echo "Image did not change. Ignore it.";
                } else {
                    $errorStr = print_r($errors, 1);
                }
                echo "\n$errorStr";
                $success = false;
            }

        } catch (Exception $e) {
            $lbf->rollbackMasterChanges(__METHOD__);
            echo "\t".$e->getMessage();
            $success = false;
        }

        return $success;
    }

    /**
     * checks if the wiki contains pages in the passed namespace, that
     * do not exist in the filesystem
     * @return void
     */
    private function checkMissingPages( $nsID, $nsName ) {
        $namespaceDir = $this->storageDirectory . '/' . $nsName;
        if (is_dir ( $namespaceDir )) {
            $allFiles = scandir($namespaceDir);

            $allPages = $this->getAllPages($nsID);

            $missingFiles = array_diff($allPages, $allFiles);
            foreach ($missingFiles as $file) {
                echo("\n\twiki contains a page for which no file exists: $file");
            }
        }
    }

    /**
     * @param int $nsID
     * @return array with all page names in passed namespace
     */
    private function getAllPages($nsID) {
        $pageNames = array ();
        $DbConnection = wfGetDB ( DB_REPLICA );

        $results = $DbConnection->select (
            array ('page'),
            array ('page_id', 'page_title', 'page_namespace'),
            "page_namespace = $nsID",
            __METHOD__,
            array () );

        if ($results->numRows () > 0) {
            foreach ( $results as $row ) {
                $pageNames [] = urlencode ( $row->page_title ) . '.' . self::FILE_EXTENSION;
            }
        }

        return $pageNames;
    }

    /**
     * @return array with all namespaces, the keys id formed by the NS-ID and the values by their names
     */
    private function getNamespaces() {
        $Namespaces = RequestContext::getMain()->getLanguage()->getNamespaces();
        $Namespaces[0] = 'Main';
        return $Namespaces;
    }

    /**
     * Get intersection of selected namespaces (from command line parameters) and all wiki namespaces
     * @return array mapping NS-IDs to namespace names
     */
    private function getRelevantNamespaces($selectedNamespaces, $allNamespaces) {
        if(is_null($selectedNamespaces)) {
            return $allNamespaces;
        } else {
            $unknownNamespaces = array_diff($selectedNamespaces, $allNamespaces);
            if(!empty($unknownNamespaces)) {
                echo("\nUnknown namespaces:");
                foreach ($unknownNamespaces as $namespace) {
                    echo("\n\t$namespace");
                }
            }

            return array_intersect($allNamespaces, $selectedNamespaces);
        }
    }
} // end of WikiImport class

$maintClass = 'WikiImportExport\WikiImport';
require_once RUN_MAINTENANCE_IF_MAIN;
