<?php

namespace DIQA\FacetedSearch2\Maintenance;

use DIQA\FacetedSearch2\ConfigTools;
use DIQA\FacetedSearch2\Exceptions\BackendException;
use DIQA\FacetedSearch2\Update\FSIndexer;
use DIQA\Formatter\Color;
use DIQA\Formatter\Config;
use DIQA\Formatter\Formatter;
use MediaWiki\Cache\LinkCache;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Exception;

/**
 * Updates the index.
 *
 */
if (!file_exists(__DIR__ . '/../../../maintenance/Maintenance.php')) {
    print "No wiki context found!\n";
    die();
}
require_once __DIR__ . '/../../../maintenance/Maintenance.php';

class UpdateIndex extends \Maintenance
{

    private LinkCache $linkCache;
    private bool $writeToStartidfile;
    private int $num_files = 0;

    private Formatter $formatter;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->addDescription("Updates the backend index used for Faceted Search 2");
        $this->addOption('v', 'Verbose mode', false, false);
        $this->addOption('g', 'Get the maximum ID of pages that would be updated (all other parameters are ignored if this is present)', false, false);
        $this->addOption('d', 'Delay every 100 pages (miliseconds)', false, true);
        $this->addOption('x', 'Debug mode', false, false);
        $this->addOption('p', 'Page title(s), separated by ","', false, true);
        $this->addOption('s', 'Start-ID', false, true);
        $this->addOption('e', 'End-ID', false, true);
        $this->addOption('n', 'Number of IDs from Start-ID', false, true);
        $this->addOption('f', 'End-ID by Pagename', false, true);
        $this->addOption('startidfile', 'File containing ID to start processing and saves last processed ID to this file', false, true);

        $useBulkUpdates = ConfigTools::getFacetedSearchUpdateClient()->supportBulkUpdates();
        $config = new Config([$useBulkUpdates ? 24 : 8, 100, 15],
            [Config::LEFT_ALIGN, Config::LEFT_ALIGN, Config::LEFT_ALIGN],
            [
                'borderPadding' => true
            ]
        );
        $config->highlightWord("[ ERROR ]", Color::fromColor(Color::BLACK, Color::RED), 2)
            ->highlightWord("[ WARNING ]", Color::fromColor(Color::BLACK, Color::YELLOW), 2)
            ->highlightWord("[ SUCCESS ]", Color::fromColor(Color::BLACK, Color::GREEN), 2);
        $this->formatter = new Formatter($config);
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        if (!defined('FS2_EXTENSION_VERSION')) {
            print("ERROR: The FacetedSearch2 extension is not properly installed or configured.\n");
            die(1);
        }

        $this->createIndexIfNecessary();

        if ($this->hasOption('g')) {
            $max = $this->getMaxId();
            print "$max\n";
            return;
        }

        // when indexing everything, we dont create any updating job for the index
        global $fsCreateUpdateJob;
        $fsCreateUpdateJob = false;

        $this->linkCache = MediaWikiServices::getInstance()->getLinkCache();
        $this->num_files = 0;
        $this->printDocHeader();

        if (!$this->hasOption('p')) {
            $startId = $this->getStartId();
            $endId = $this->getEndId($startId);
            if (ConfigTools::getFacetedSearchUpdateClient()->supportBulkUpdates()) {
                $this->refreshPagesByIdsBulk($startId, $endId);
            } else {
                $this->refreshPagesByIds($startId, $endId);
            }
        } else {
            $pages = explode(',', $this->getOption('p'));
            $this->refreshPages($pages);
        }

        print "\n\n{$this->num_files} IDs refreshed.\n";
    }

    /**
     * Print Documentation header
     */
    private function printDocHeader(): void
    {
        print "Refreshing all semantic data in the index server!\n---\n" .
            " Some versions of PHP suffer from memory leaks in long-running scripts.\n" .
            " If your machine gets very slow after many pages (typically more than\n" .
            " 1000) were refreshed, please abort with CTRL-C and resume this script\n" .
            " at the last processed page id using the parameter -s (use -v to display\n" .
            " page ids during refresh). \n\n[Use -x for debugging information]\n\n" .
            "Continue this until all pages were refreshed.\n---\n";

    }

    /**
     * Refresh all pages from ID start to ID end
     * Writes last processed ID to a file if option 'startidfile' is set.
     *
     * @param int $start
     * @param int $end
     * @throws Exception
     */
    private function refreshPagesByIds(int $start, int $end): void
    {
        print "Processing all IDs from $start to " . ($end ? "$end" : 'last ID') . " ...\n";

        $id = $start;
        while (((!$end) || ($id <= $end)) && ($id > 0)) {
            $title = Title::newFromID($id);

            if (is_null($title)) {
                $id++;
                continue;
            }

            $this->updateIndex($title, $id);

            $id++;

            if (($this->hasOption('d')) && (($this->num_files + 1) % 100 === 0)) {
                usleep($this->getOption('d'));
            }
            $this->num_files++;
            $this->linkCache->clear(); // avoid memory leaks

            if ($this->writeToStartidfile) {
                file_put_contents($this->getOption('startidfile'), "$id");
            }
        }

    }


    /**
     * Refresh all pages from ID start to ID end using bulk updates.
     * Collects Title objects in batches and passes them as an array to updateIndexWithBatch().
     * Writes last processed ID to a file if option 'startidfile' is set.
     *
     * @param int $start
     * @param int $end
     * @throws Exception
     */
    private function refreshPagesByIdsBulk(int $start, int $end): void
    {
        print "\nProcessing all IDs from $start to " . ($end ? "$end" : 'last ID') . " ...";
        print "\nUsing bulk mode";
        print "\n";

        $batchSize = 100;
        $titles = [];
        $id = $start;
        $lastIdInBatch = $start;

        while (((!$end) || ($id <= $end)) && ($id > 0)) {
            $title = Title::newFromID($id);

            $id++;
            if (is_null($title)) {
                continue;
            }

            $titles[] = $title;
            $lastIdInBatch = $id;
            $this->num_files++;

            if (count($titles) >= $batchSize) {

                $this->updateIndexWithBatch($titles, $start, $lastIdInBatch);
                $start = $lastIdInBatch + 1;
                $titles = [];

                if ($this->hasOption('d')) {
                    usleep($this->getOption('d'));
                }
                $this->linkCache->clear(); // avoid memory leaks

                if ($this->writeToStartidfile) {
                    file_put_contents($this->getOption('startidfile'), "$lastIdInBatch");
                }
            }
        }

        // flush remaining titles
        if (count($titles) > 0) {

            $this->updateIndexWithBatch($titles, $start, $lastIdInBatch);
            $this->linkCache->clear();

            if ($this->writeToStartidfile) {
                file_put_contents($this->getOption('startidfile'), "$lastIdInBatch");
            }
        }
    }

    /**
     * @throws Exception
     */
    private function refreshPages($pages): void
    {
        print "Refreshing specified pages!\n\n";

        foreach ($pages as $page) {

            $page = trim($page);
            if ($this->getOption('v')) {
                print sprintf("(%s) Processing page %s ... \n", $this->num_files, $page);
            }

            $title = Title::newFromText($page);

            if (!is_null($title)) {
                $this->updateIndex($title);
            }

            $this->num_files++;
        }

    }

    /**
     * @throws Exception
     */
    private function updateIndex($title, $id = null): void
    {

        try {
            $messages = [];
            FSIndexer::indexArticles([$title], $messages);

            if ($this->hasOption('v')) {
                print $this->formatter->formatLine($id ?? '', $title->getPrefixedText(), "[ SUCCESS ]");
                print "\n";
            }
            $this->logWarnings($messages);
        } catch (Exception $e) {
            print $this->formatter->formatLine($id ?? '', $title->getPrefixedText(), "[ ERROR ]");
            print "\n";
            $this->logExtendedErrorInfo($e);
        }
    }

    /**
     * @throws Exception
     */
    private function updateIndexWithBatch(array $titles, $start, $end): void
    {

        try {
            $messages = [];
            $this->logBulkProgress($start, $end, $titles, "");
            FSIndexer::indexArticles($titles, $messages);
            $this->logBulkProgress($start, $end, $titles, "[ SUCCESS ]");
            $this->logWarnings($messages);
        } catch (Exception $e) {
            $this->logBulkProgress($start, $end, $titles, "[ ERROR ]");
            $this->logExtendedErrorInfo($e);
        }
    }

    /**
     * Calculates startID of MW-page
     * Reads ID from a file if option 'startidfile' is specified.
     *
     * @return int
     */
    private function getStartId(): int
    {
        $this->writeToStartidfile = false;
        if ($this->hasOption('s')) {
            $start = max(1, intval($this->getOption('s')));
        } elseif ($this->hasOption('startidfile')) {
            if (!is_writable(file_exists($this->getOption('startidfile')) ? $this->getOption('startidfile') : dirname($this->getOption('startidfile')))) {
                die("Cannot use a startidfile that we can't write to.\n");
            }
            $this->writeToStartidfile = true;
            if (is_readable($this->getOption('startidfile'))) {
                $start = max(1, intval(file_get_contents($this->getOption('startidfile'))));
            } else {
                $start = 1;
            }
        } else {
            $start = 1;
        }
        return $start;
    }

    /**
     * Calculates endID of MW-page
     *
     * @param int $start Start-ID
     *
     * @return int
     */
    private function getEndId($start): int
    {
        if ($this->hasOption('e')) {
            // Note: this might reasonably be larger than the page count
            $end = intval($this->getOption('e'));

        } elseif ($this->hasOption('n')) {
            $end = $start + intval($this->getOption('n'));

        } elseif ($this->hasOption('f')) {
            $title = Title::newFromText($this->getOption('f'));
            $start = $title->getArticleID();
            $end = $title->getArticleID();

        } else {
            $end = $this->getMaxId();
        }
        return $end;
    }

    private function getMaxId(): int
    {
        $db = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection(DB_REPLICA);
        $page_table = $db->tableName("page");
        $query = "SELECT MAX(page_id) as maxid FROM $page_table";
        $res = $db->query($query);
        if ($res->numRows() > 0) {
            $row = $res->fetchObject();
            if ($row) {
                return $row->maxid;
            }
        }
        return 0;
    }

    private function createIndexIfNecessary(): void
    {

        try {
            $client = ConfigTools::getFacetedSearchUpdateClient();
            if ($client->existsIndex()) {
                if ($this->confirm("\nIndex already exists. Clear all documents and continue? (yes/no): ")) {
                    $client->deleteIndex();
                    $client->initIndex();
                    print "\nIndex was deleted and re-created.\n";
                } else {
                    print "\nAborted.\n";
                    die(1);
                }
            } else {
                if ($client->initIndex()) {
                    print "\nIndex created.\n";
                }
            }
            $client->refreshIndex();

        } catch (BackendException $e) {
            print("\nERROR: Creating the index failed. Reason: " . $e->getMessage());
            print "\n";
            die(1);
        }
    }

    private function confirm(string $prompt): bool
    {
        while (true) {
            print $prompt;
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);
            $answer = strtolower(trim((string)$line));
            if ($answer === 'yes' || $answer === 'y') {
                return true;
            }
            if ($answer === 'no' || $answer === 'n') {
                return false;
            }
            print "Please answer 'yes' or 'no'.\n";
        }
    }

    private function logBulkProgress(int $start, int $end, array $titles, string $status): void
    {
        if (!$this->hasOption('v')) {
            return;
        }
        $startTitle = $titles[0]->getPrefixedText();
        $endTitle = $titles[count($titles) - 1]->getPrefixedText();
        print $this->formatter->formatLine(sprintf("IDs [%s to %s]", $start, $end),
            sprintf("[%s to %s]...", self::shorten($startTitle), self::shorten($endTitle)),
            $status);
        if ($status === '') {
            print "\r";
        } else {
            print "\n";
        }
    }

    private static function shorten(string $s): string
    {
        return mb_strlen($s) > 50 ? trim(substr($s, 0, 50)) . "..." : $s;
    }


    /**
     * @throws Exception
     */
    public function logExtendedErrorInfo(Exception $e): void
    {
        if ($this->hasOption('x')) {
            print $this->formatter->formatLine('', sprintf('HTTP code %s', $e->getCode()), '');
            print "\n";
            print $this->formatter->formatLine('', str_replace(["\n", "\r"], ' ', strip_tags($e->getMessage())), '');
            print "\n";
        }
    }

    /**
     * @param array $messages
     * @return void
     * @throws Exception
     */
    public function logWarnings(array $messages): void
    {
        if (count($messages) > 0) {
            print $this->formatter->formatLine('', implode(' ', $messages), "[ WARNING ]");
            print "\n";
        }
    }
}

global $maintClass;
$maintClass = "DIQA\FacetedSearch2\Maintenance\UpdateIndex";
require_once RUN_MAINTENANCE_IF_MAIN;
