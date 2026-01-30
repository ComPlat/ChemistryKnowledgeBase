<?php

use DIQA\Formatter\Color;
use DIQA\Formatter\Config;
use DIQA\Formatter\Formatter;
use DIQA\PageImport\EditWikiPage;
use DIQA\PageImport\LoggerUtils;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Maintenance\Maintenance;
use MediaWiki\Title\Title;

error_reporting(E_ERROR);

// @codeCoverageIgnoreStart
require_once __DIR__ . '/../../../maintenance/Maintenance.php';
// @codeCoverageIgnoreEnd


class WikiImport extends Maintenance
{

    const string SKIPPED = "[ skipped  ]";
    const string UPDATED = "[ updated  ]";
    const string UPLOADED = "[ uploaded ]";
    const string CREATED = "[ created  ]";
    const string CONFLICT = "[ conflict ]";
    const string ERROR = "[  error   ]";
    const string ONLY_LF = "[ only LF  ]"; // differ only in line feeds, actually means "skipped"

    /// File extension of wiki files
    const string FILE_EXTENSION = 'wiki';

    /// Subject for creating or updating pages
    const string SUBJECT = 'Added to the wiki via WikiImport-Script.';

    /// Array of namespaces that should be imported
    private ?array $selectedNamespaces = null;

    /// Array of all namespaces in the Wiki
    private array $allNamespaces;

    /// Storage directory.
    private string $storageDirectory;

    /// Contains all pages which were not written because last revision was not auto-generated
    private array $skipDueToConflict = [];
    private array $updatedOrCreated = [];
    private array $uploadedFiles = [];

    private Formatter $formatter;
    private LoggerUtils $logger;
    private FileRepo $fileRepo;

    private int $count;

    public function __construct()
    {
        parent::__construct();

        $this->addDescription('Script for importing wiki pages to readable individual files.');
        $this->addOption('directory', 'files will be imported from subfolders (=namespaces) of this directory', false, true);
        $this->addOption('namespace', 'Only import the given namespaces (comma-separated names)', false, true);
        $this->addOption('verbose', 'Show more details in the log output', false, false, 'v');
        $this->addOption('force', 'Force import even if last revision is not auto-generated', false, false, 'f');
        $this->addOption('uploadFiles', 'Upload files (non *.wiki pages in Files folder)', false, false, 'u');
    }

    public function execute(): void
    {

        $this->logger = new LoggerUtils('WikiImporter', 'PageImport', "OFF", false);
        $this->fileRepo = MediaWikiServices::getInstance()->getRepoGroup()->getLocalRepo();
        $this->initFormatter();

        $this->allNamespaces = $this->getAllNamespaces();
        $this->processOptions();
        $this->doWikiImport();
    }


    private function processOptions(): void
    {
        $namespaces = $this->getOption('namespace', null);
        if ($namespaces != null) {
            $this->selectedNamespaces = explode(',', $namespaces);
        } else {
            $this->selectedNamespaces = null;
        }

        $directory = $this->getOption('directory', null);
        if ($directory != null) {
            $this->storageDirectory = $directory;
        } else {
            $this->storageDirectory = getcwd();
        }
    }

    private function doWikiImport(): void
    {
        $relevantNamespaces = $this->getRelevantNamespaces($this->selectedNamespaces, $this->allNamespaces);
        $this->printLine("\nImporting files...");

        foreach ($relevantNamespaces as $nsID => $nsName) {
            $this->printLine("\n$nsName");

            $this->importNamespace($nsID, $nsName);
            if ($this->hasOption('verbose')) {
                $this->checkMissingPages($nsID, $nsName);
            }
        }

        if (count($this->updatedOrCreated) > 0) {
            $this->printLine("\n");
            $this->printLine("Following pages were created/updated:\n");
            foreach ($this->updatedOrCreated as $p) {
                $this->printLine(" - {$p->getPrefixedText()}");
            }
        }

        if (count($this->uploadedFiles) > 0) {
            $this->printLine("\n");
            $this->printLine("Following files were uploaded:\n");
            foreach ($this->uploadedFiles as $p) {
                $this->printLine(" - {$p}");
            }
        }

        if (count($this->skipDueToConflict) > 0) {
            $this->printLine("\n");
            $this->printLine("Following pages were skipped due to conflicts:\n");
            foreach ($this->skipDueToConflict as $p) {
                $this->printLine(" - {$p->getPrefixedText()}");
            }
        }

        $this->printLine("\n");
    }

    private function importNamespace(int $nsID, string $nsName): void
    {
        $this->count = 0;
        $namespaceDir = $this->storageDirectory . '/' . $nsName;

        if (!is_dir($namespaceDir)) {
            $this->printLine("\tno files.\n");
            return;
        }

        $allFiles = scandir($namespaceDir);
        foreach ($allFiles as $file) {
            if (is_dir("$namespaceDir/$file")) {
                continue;
            }

            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);

            if ($nsID === NS_FILE) {

                if ($fileExtension == self::FILE_EXTENSION) {
                    $this->importWikiPage($nsID, $nsName, "$namespaceDir/$file");

                } else if ($this->hasOption('uploadFiles')) {
                    global $wgFileExtensions;
                    if (in_array($fileExtension, $wgFileExtensions)) {
                        $this->uploadFile("$namespaceDir/$file", $file);
                    } else {
                        $this->printLine($this->formatter->formatLine("file type not supported: $fileExtension", self::SKIPPED));
                    }

                }

            } else if ($fileExtension == self::FILE_EXTENSION) {
                $this->importWikiPage($nsID, $nsName, "$namespaceDir/$file");
            }

        }
        $this->printLine("\t{$this->count} file(s) imported.\n");

    }


    private function importWikiPage(int $nsID, string $nsName, $filePath): void
    {
        $encodedFileName = pathinfo($filePath, PATHINFO_FILENAME);

        if (empty($encodedFileName)) {
            $this->printLine($this->formatter->formatLine("ignoring $filePath -- It has no title", self::SKIPPED));
            return;
        }

        $decodedFileName = urldecode($encodedFileName);

        if ($nsID == 0) {
            $pageTitle = Title::newFromText($decodedFileName);
        } else {
            $pageTitle = Title::newFromText("$nsName:$decodedFileName");
        }

        $newContentString = file_get_contents($filePath);
        $newContentString = trim($newContentString);

        $flags = $pageTitle->exists() ? EDIT_UPDATE : EDIT_NEW;

        if (self::isPredefinedPage($pageTitle)) {
            return;
        }

        if ($pageTitle->exists() && !$this->hasOption('force')) {
            $revision = MediaWikiServices::getInstance()->getRevisionStore()->getRevisionByTitle($pageTitle);
            if (!is_null($revision) && !self::isAutoGenerated($revision)) {
                $oldText = $revision->getContent(SlotRecord::MAIN)->getWikitextForTransclusion();
                if (EditWikiPage::equalIgnoringLineFeeds($newContentString, $oldText)) {
                    $this->printLine($this->formatter->formatLine($pageTitle->getText(), self::ONLY_LF));
                    return;
                }
                $this->printLine($this->formatter->formatLine($pageTitle->getText(), self::CONFLICT));
                $this->skipDueToConflict[] = $pageTitle;
                return;
            }
        }

        $success = EditWikiPage::doEditContent($pageTitle, $newContentString, self::SUBJECT, $flags);
        if ($success === EditWikiPage::NOT_UPDATED) {
            $this->printLine($this->formatter->formatLine($pageTitle->getText(), self::SKIPPED));
        } elseif ($success === EditWikiPage::ERROR) {
            $this->printLine($this->formatter->formatLine($pageTitle->getText(), self::ERROR), 'error');
        } else if ($success === EditWikiPage::UPDATED) {
            $this->printLine($this->formatter->formatLine($pageTitle->getText(), self::UPDATED));
            $this->updatedOrCreated[] = $pageTitle;
            $this->count++;
        } else if ($success === EditWikiPage::CREATED) {
            $this->printLine($this->formatter->formatLine($pageTitle->getText(), self::CREATED));
            $this->updatedOrCreated[] = $pageTitle;
            $this->count++;
        }

    }

    private function checkMissingPages(int $nsID, string $nsName): void
    {
        $namespaceDir = $this->storageDirectory . '/' . $nsName;
        if (!is_dir($namespaceDir)) {
            return;
        }
        $allFiles = scandir($namespaceDir);
        $allFiles = array_map(fn($file) => ucfirst($file), $allFiles);
        $allPages = self::getAllPagesByNamespace($nsID);

        $missingFiles = array_diff($allPages, $allFiles);
        foreach ($missingFiles as $file) {
            $this->printLine($this->formatter->formatLine("wiki contains a page for which no file exists: $file", self::SKIPPED));
        }
    }

    private static function getAllPagesByNamespace(int $nsID): array
    {
        $pageNames = array();
        $DbConnection = wfGetDB(DB_REPLICA);

        $results = $DbConnection->select(
            array('page'),
            array('page_id', 'page_title', 'page_namespace'),
            "page_namespace = $nsID",
            __METHOD__,
            array());

        if ($results->numRows() > 0) {
            foreach ($results as $row) {
                $pageNames [] = urlencode($row->page_title) . '.' . self::FILE_EXTENSION;
            }
        }

        return $pageNames;
    }

    private function getAllNamespaces(): array
    {
        $Namespaces = RequestContext::getMain()->getLanguage()->getNamespaces();
        $Namespaces[0] = 'Main';
        return $Namespaces;
    }


    private function getRelevantNamespaces($selectedNamespaces, $allNamespaces): array
    {
        if (is_null($selectedNamespaces)) {
            return $allNamespaces;
        }
        $unknownNamespaces = array_diff($selectedNamespaces, $allNamespaces);
        if (!empty($unknownNamespaces)) {
            $this->printLine("ERROR: Unknown namespaces:\n", "error");
            foreach ($unknownNamespaces as $namespace) {
                $this->printLine("\t$namespace\n");
            }
        }

        return array_intersect($allNamespaces, $selectedNamespaces);
    }

    private static function isAutoGenerated($revision): bool
    {
        return ($revision->getComment()->text === 'auto-generated' || $revision->getComment()->text === self::SUBJECT);
    }

    private static function isPredefinedPage(Title $title): bool
    {

        // note: for some reason property namespace is not canonical
        if (defined("SMW_NS_PROPERTY") && $title->getNamespace() === SMW_NS_PROPERTY) {
            return in_array($title->getText(), [
                "Foaf:homepage",
                "Foaf:knows",
                "Foaf:name",
                "Owl:differentFrom"]);
        }

        $nsText = MediaWikiServices::getInstance()->getNamespaceInfo()->
        getCanonicalName($title->getNamespace());
        return in_array("$nsText:{$title->getText()}", [
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
        $config->highlightWord(self::SKIPPED, Color::fromColor(COLOR::BLACK, Color::LIGHT_GREY), 1)
            ->highlightWord(self::UPDATED, Color::fromColor(COLOR::BLACK, Color::GREEN), 1)
            ->highlightWord(self::UPLOADED, Color::fromColor(COLOR::BLACK, Color::GREEN), 1)
            ->highlightWord(self::CREATED, Color::fromColor(COLOR::BLACK, Color::GREEN), 1)
            ->highlightWord(self::CONFLICT, Color::fromColor(COLOR::BLACK, Color::MAGENTA), 1)
            ->highlightWord(self::ERROR, Color::fromColor(COLOR::BLACK, Color::RED), 1)
            ->highlightWord(self::ONLY_LF, Color::fromColor(COLOR::BLACK, Color::YELLOW), 1)
            ->setLeftColumnPadding(0, 3);
        $this->formatter = new Formatter($config);
    }

    private function printLine($line, $lvl = 'log'): void
    {
        $trimmedLine = trim(self::removeEscapeSeqs($line));
        if ($trimmedLine !== '') {
            $this->logger->{$lvl}($trimmedLine);
        }
        echo "\n$line";
    }

    private static function removeEscapeSeqs($s): string
    {
        return preg_replace('/\033\[[^]]+m/', '', $s);
    }

    private function uploadFile(string $location, string $filename): void
    {

        $decodedFileName = urldecode($filename);
        $fileTitle = Title::newFromText($decodedFileName, NS_FILE);
        $fileInRepo = $this->fileRepo->newFile($fileTitle);
        $hasOldFile = false;
        if ($fileTitle->exists() && $fileInRepo->getLocalRefPath() !== false) {
            $hasOldFile = true;
            $hashOld = md5(file_get_contents($fileInRepo->getLocalRefPath()));
            $fileData = file_get_contents($location);
            $hashNew = md5($fileData);
            if ($hashOld === $hashNew) {
                $this->printLine($this->formatter->formatLine($fileTitle->getText() . " -- same content", self::SKIPPED));
                return;
            }
        }
        $status = $fileInRepo->upload(new FSFile($location), "auto-generated", "");
        if ($status->isOK()) {
            $this->printLine($this->formatter->formatLine("File uploaded '" . $fileTitle->getText() . "'", $hasOldFile ? self::UPDATED : self::UPLOADED));
            $this->uploadedFiles[] = $filename;
            $this->count++;
        } else {
            $this->printLine($this->formatter->formatLine($fileTitle->getText(), self::ERROR));
            $this->printLine($this->formatter->formatLine(implode(", ", $status->getErrorsArray()[0]), ''));
        }
    }

}

$maintClass = 'WikiImport';
require_once RUN_MAINTENANCE_IF_MAIN;
