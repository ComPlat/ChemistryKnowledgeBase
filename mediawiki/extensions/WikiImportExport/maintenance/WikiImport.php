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

use Maintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use RequestContext;
use Title;

error_reporting(E_ERROR);

require_once __DIR__ . '/../../../maintenance/Maintenance.php';
require_once 'EditWikiPage.php';

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

    /// Contains all pages which were not written because last revision was not auto-generated
    private $skipDueToConflict = [];

    /// Contains all pages which were created/updated
    private $createdOrUpdatedPages = [];

    public function __construct() {
        parent::__construct();

        $this->addDescription( 'Script for exporting wiki pages to readable individual files.' );
        $this->addOption( 'directory', 'files will be exported to subfolder of this diretory', false, true );
        $this->addOption( 'namespace', 'Only show/count jobs of a given type', false, true );
        $this->addOption( 'verbose', 'Show more details in the log output', false, false, 'v' );
        $this->addOption( 'force', 'Force import even if last revision is not auto-generated', false, false, 'f' );
    }

    public function execute() {
        // Get all namespaces.
        $this->allNamespaces = $this->getNamespaces();
        $this->processOptions();
        $this->doWikiImport();
    }

    /**
     * parse the commandline options amd populate $this->selectedNamespaces and $this->storageDirectory
     * @return void
     */
    private function processOptions() {
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

        echo("Importing files...\n");

        foreach ( $relevantNamespaces as $nsID => $nsName ) {
            echo("$nsName\n");

            $this->importNamespace ( $nsID, $nsName );
            $this->checkMissingPages ( $nsID, $nsName );
        }

        if (count($this->createdOrUpdatedPages) > 0) {
            echo "\n\nFollowing pages were created/updated:\n";
            foreach($this->createdOrUpdatedPages as $p) {
                echo "\n - {$p->getPrefixedText()}";
            }
            echo "\n\n";
        }

        if (count($this->skipDueToConflict) > 0) {
            echo "\n\nFollowing pages were skipped due to conflicts:\n";
            foreach($this->skipDueToConflict as $p) {
                echo "\n - {$p->getPrefixedText()}";
            }
            echo "\n\n";
        }
    }

    /**
     * import the files for this single namespace.
     * @param int    $nsId   ID of the namespace
     * @param string $nsName string representation of the namespace
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
                    $imageDir = $this->storageDirectory . '/File';
                    $imageFilenameInWiki = pathinfo($file, PATHINFO_FILENAME) . ".". pathinfo($file, PATHINFO_EXTENSION);
                    $title = \Title::newFromDBkey( "File:$imageFilenameInWiki");
                    $this->uploadImage("$imageDir/$imageFilenameInWiki", $title);
                }
            }
        } else {
            echo("\tno files.\n");
        }
    }

    function uploadImage($path, $title) {
        $localFile = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo()->newFile( $title );
        $status = $localFile->upload($path, "auto-generated", "");
        if ($status->isOK()) {
            echo "\n\tfile uploaded: ".$title->getPrefixedText()."\n";
            $this->createdOrUpdatedPages[] = $title;
        } else {
            foreach($status->getErrors() as $e) {
                echo "\n\t".$e['message'].": ".$title->getPrefixedText()."\n";
            }
        }
    }

    /**
     * import a single file.
     * @param int    $nsId   ID of the namespace
     * @param string $nsName string representation of the namespace
     * @param string $file   filename including path of the file to import
     * @return void
     */
    private function importFile($nsID, $nsName, $file) {
        $encodedFileName = pathinfo($file, PATHINFO_FILENAME);

        if (empty($encodedFileName)) {
            echo("\tignoring $file -- It has no title.\n");
            return;
        }

        $decodedFileName = urldecode($encodedFileName);

        if ($nsID == 0) {
            $pageTitle = Title::newFromText($decodedFileName);
        } else {
            $pageTitle = Title::newFromText("$nsName:$decodedFileName");
        }

        $newContentString = file_get_contents($file);
        $newContentString = trim($newContentString);

        if ($pageTitle->exists()) {
            $flags = EDIT_UPDATE;
        } else     {
            $flags = EDIT_NEW;
        }

        if (self::isPredefinedPage($pageTitle)) {
            return;
        }

        if ($pageTitle->exists() && !$this->hasOption('force')) {
            $revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionByTitle( $pageTitle );
            if (!is_null($revision) && !$this->isAutoGenerated($revision)) {
                $oldText = $revision->getContent( SlotRecord::MAIN )->getWikitextForTransclusion();
                if (EditWikiPage::equalIgnoringLineFeeds($newContentString, $oldText)) {
                    echo("\tWARN: conflicting, but equal: $decodedFileName\n");
                    return;
                }
                echo("\tWARN: skipping due to conflict: $decodedFileName\n");
                $this->skipDueToConflict[] = $pageTitle;
                return;
            }
        }

        $success = EditWikiPage::doEditContent( $pageTitle, $newContentString, self::SUBJECT, $flags );
        if ( $success === EditWikiPage::ERROR ) {
            echo("\tERROR: creating $decodedFileName\n");
        } else if ( $success === EditWikiPage::UPDATED ) {
            $this->createdOrUpdatedPages[] = $pageTitle;
        }

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
                echo("\twiki contains a page for which no file exists: $file\n");
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
                echo("ERROR: Unknown namespaces:\n");
                foreach ($unknownNamespaces as $namespace) {
                    echo("\t$namespace\n");
                }
            }

            return array_intersect($allNamespaces, $selectedNamespaces);
        }
    }

    /**
     * @param $revision
     * @return bool
     */
    private function isAutoGenerated($revision): bool
    {
        return ($revision->getComment()->text === 'auto-generated' || $revision->getComment()->text === 'Added to the wiki via WikiImport-Script.');
    }

    private static function isPredefinedPage($title) {
        return in_array($title->getPrefixedText(), [
            "MediaWiki:Smw import foaf",
            "MediaWiki:Smw import owl",
            "MediaWiki:Smw import skos",
            "Property:Foaf:homepage",
            "Property:Foaf:knows",
            "Property:Foaf:name",
            "Property:Owl:differentFrom",
            "smw/schema:Group:Predefined properties",
            "smw/schema:Group:Schema properties"
        ]);
    }
} // end of WikiImport class

$maintClass = 'WikiImportExport\WikiImport';
require_once RUN_MAINTENANCE_IF_MAIN;
