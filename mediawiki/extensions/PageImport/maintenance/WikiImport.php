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


use DIQA\Formatter\Color;
use DIQA\Formatter\Config;
use DIQA\Formatter\Formatter;
use DIQA\PageImport\EditWikiPage;
use DIQA\PageImport\LoggerUtils;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;

error_reporting(E_ERROR);

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

class WikiImport extends Maintenance {

    const SKIPPED  = "[ skipped  ]";
    const UPDATED  = "[ updated  ]";
    const CREATED  = "[ created  ]";
    const CONFLICT = "[ conflict ]";
    const ERROR    = "[  error   ]";
    const ONLY_LF  = "[ only LF  ]"; // differ only in line feeds, actually means "skipped"

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

    private $formatter;
    private $logger;
    private FileRepo $fileRepo;

    public function __construct() {
        parent::__construct();

        $this->addDescription( 'Script for exporting wiki pages to readable individual files.' );
        $this->addOption( 'directory', 'files will be exported to subfolder of this diretory', false, true );
        $this->addOption( 'namespace', 'Only show/count jobs of a given type', false, true );
        $this->addOption( 'verbose', 'Show more details in the log output', false, false, 'v' );
        $this->addOption( 'force', 'Force import even if last revision is not auto-generated', false, false, 'f' );
    }

    public function execute() {

        $this->logger = new LoggerUtils('WikiImporter', 'PageImport', "OFF", false);
        $this->fileRepo = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo();
        $this->initFormatter();

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

        $this->printLine("\nImporting files...");

        foreach ( $relevantNamespaces as $nsID => $nsName ) {
            $this->printLine("\n$nsName");

            $this->importNamespace ( $nsID, $nsName );
            $this->checkMissingPages ( $nsID, $nsName );
        }
        $this->printLine("\n");

        if (count($this->skipDueToConflict) > 0) {
            $this->printLine("Following pages were skipped due to conflicts:\n");
            foreach($this->skipDueToConflict as $p) {
                $this->printLine(" - {$p->getPrefixedText()}");
            }
            $this->printLine("\n");
        }
    }

    /**
     * import the files for this single namespace.
     * @param int    $nsId   ID of the namespace
     * @param string $nsName string representation of the namespace
     * @return void
     */
    private function importNamespace( $nsID, $nsName ) {
        $count = 0;
        $namespaceDir = $this->storageDirectory . '/' . $nsName;
        if (is_dir ( $namespaceDir )) {
            $allFiles = scandir($namespaceDir);
            foreach ($allFiles as $file) {
                if (is_dir("$namespaceDir/$file")) {
                    continue;
                }

                if ($nsID === NS_FILE) {
                    global $wgFileExtensions;
                    $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                    if (!in_array($fileExtension, $wgFileExtensions)) {
                        $this->printLine("\tfile type not supported: $fileExtension\n");
                        continue;
                    }
                    $imported = $this->uploadFile("$namespaceDir/$file", $file);
                    if ($imported) {
                        $count++;
                    }
                } else {
                    $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
                    if ($fileExtension == self::FILE_EXTENSION) {
                        $imported = $this->importFile($nsID, $nsName, "$namespaceDir/$file");
                        if ($imported) {
                            $count++;
                        }
                    }
                }
            }
            $this->printLine("\t$count file(s) imported.\n");
        } else {
            $this->printLine("\tno files.\n");
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
            $this->printLine("\tignoring $file -- It has no title.\n");
            return false;
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
            return false;
        }

        if ($pageTitle->exists() && !$this->hasOption('force')) {
            $revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionByTitle( $pageTitle );
            if (!is_null($revision) && !$this->isAutoGenerated($revision)) {
                $oldText = $revision->getContent( SlotRecord::MAIN )->getWikitextForTransclusion();
                if (EditWikiPage::equalIgnoringLineFeeds($newContentString, $oldText)) {
                    $this->printLine($this->formatter->formatLine($pageTitle->getText(), self::ONLY_LF));
                    return false;
                }
                $this->printLine($this->formatter->formatLine( $pageTitle->getText(), self::CONFLICT));
                $this->skipDueToConflict[] = $pageTitle;
                return false;
            }
        }

        $success = EditWikiPage::doEditContent( $pageTitle, $newContentString, self::SUBJECT, $flags );
        if ( $success === EditWikiPage::NOT_UPDATED ) {
            $this->printLine($this->formatter->formatLine( $pageTitle->getText(), self::SKIPPED));
            return false;
        } elseif ( $success === EditWikiPage::ERROR ) {
            $this->printLine($this->formatter->formatLine( $pageTitle->getText(), self::ERROR), 'error');
            return false;
        } else if ( $success === EditWikiPage::UPDATED ) {
            $this->printLine($this->formatter->formatLine( $pageTitle->getText(), self::UPDATED));
            return true;
        } else if ( $success === EditWikiPage::CREATED ) {
            $this->printLine($this->formatter->formatLine( $pageTitle->getText(), self::CREATED));
            return true;
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
                $this->printLine("\twiki contains a page for which no file exists: $file\n");
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
                if ($nsID === NS_FILE) {
                    $pageNames [] = urlencode($row->page_title);
                } else {
                    $pageNames [] = urlencode($row->page_title) . '.' . self::FILE_EXTENSION;
                }
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
                $this->printLine("ERROR: Unknown namespaces:\n", "error");
                foreach ($unknownNamespaces as $namespace) {
                    $this->printLine("\t$namespace\n");
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
        return ($revision->getComment()->text === 'auto-generated' || $revision->getComment()->text === self::SUBJECT);
    }

    private static function isPredefinedPage(Title $title) {

        // note: for some reason property namespace is not canonical
        if (defined("SMW_NS_PROPERTY") && $title->getNamespace() === SMW_NS_PROPERTY) {
            return in_array($title->getText(), [
                "Foaf:homepage",
                "Foaf:knows",
                "Foaf:name",
                "Owl:differentFrom"]);
        }

        $nsText = MediaWikiServices::getInstance()->getNamespaceInfo()->
        getCanonicalName( $title->getNamespace() );
        return in_array( "$nsText:{$title->getText()}" , [
            "MediaWiki:Smw import foaf",
            "MediaWiki:Smw import owl",
            "MediaWiki:Smw import skos",
            "smw/schema:Group:Predefined properties",
            "smw/schema:Group:Schema properties"
        ]);
    }

    private function initFormatter(): void
    {
        $config = new Config([80, 20], [Config::LEFT_ALIGN, Config::LEFT_ALIGN]);
        $config->highlightWord(self::SKIPPED, Color::fromColor(COLOR::BLACK, Color::LIGHT_GREY), 1);
        $config->highlightWord(self::UPDATED, Color::fromColor(COLOR::BLACK, Color::GREEN), 1);
        $config->highlightWord(self::CREATED, Color::fromColor(COLOR::BLACK, Color::GREEN), 1);
        $config->highlightWord(self::CONFLICT, Color::fromColor(COLOR::BLACK, Color::MAGENTA), 1);
        $config->highlightWord(self::ERROR, Color::fromColor(COLOR::BLACK, Color::RED), 1);
        $config->highlightWord(self::ONLY_LF, Color::fromColor(COLOR::BLACK, Color::YELLOW), 1);
        $config->setLeftColumnPadding(0,3);
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

    private function uploadFile(string $location, string $filename)
    {

        $decodedFileName = urldecode($filename);
        $fileTitle = Title::newFromText($decodedFileName, NS_FILE);
        $fileInRepo = $this->fileRepo->newFile($fileTitle);
        if ($fileTitle->exists()) {
            $hashOld = md5(file_get_contents($fileInRepo->getLocalRefPath()));
            $fileData = file_get_contents($location);
            $hashNew = md5($fileData);
            if ($hashOld === $hashNew) {
                $this->printLine("\tignoring $filename -- same content.\n");
                return false;
            }
        }
        $status = $fileInRepo->upload(new FSFile($location), "auto-generated", "");
        if ($status->isOK()) {
            $this->printLine("\timported file: $filename");
            return true;
        } else {
            $this->printLine("\tupload failed due to errors: $filename");
            $this->printLine(print_r($status->getErrorsArray(), true));
            return false;
        }
    }

} // end of WikiImport class

$maintClass = 'WikiImport';
require_once RUN_MAINTENANCE_IF_MAIN;
