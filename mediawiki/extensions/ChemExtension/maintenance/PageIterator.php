<?php

use MediaWiki\MediaWikiServices;

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

abstract class PageIterator extends Maintenance
{

    private $linkCache;
    private $writeToStartidfile;
    protected $problems;

    public function __construct()
    {
        parent::__construct();

        $this->addOption('v', 'Verbose mode', false, false);
        $this->addOption('d', 'Delay every 100 pages (miliseconds)', false, true);
        $this->addOption('x', 'Debug mode', false, false);
        $this->addOption('p', 'Page title(s), separated by ","', false, true);
        $this->addOption('s', 'Start-ID', false, true);
        $this->addOption('e', 'End-ID', false, true);
        $this->addOption('n', 'Number of IDs from Start-ID', false, true);
        $this->addOption('f', 'End-ID by Pagename', false, true);
        $this->addOption('startidfile', 'File containing ID to start processing and saves last processed ID to this file', false, true);
        $this->problems = [];
    }

    public function execute()
    {
        $this->init();
        // when indexing everything, dependent pages do not need special treatment

        $this->linkCache = MediaWikiServices::getInstance()->getLinkCache();
        $this->num_files = 0;
        $this->printDocHeader();

        if (!$this->hasOption('p')) {
            $startId = $this->getStartId();
            $endId = $this->getEndId($startId);
            $this->processPagesById($startId, $endId);
        } else {
            $pages = explode(',', $this->getOption('p'));
            $this->processPages($pages);
        }
        \Hooks::run('CleanupChemExtState');
        print "\n{$this->num_files} IDs refreshed.\n";

        if (count($this->problems) > 0) {
            print "\nFound problems on following pages: \n";
            foreach ($this->problems as $p) {
                print "\t" . $p->getPrefixedText() . "\n";
            }
        }
    }

    /**
     * Print Documatation header
     */
    private function printDocHeader()
    {
        print "\n---\n" .
            " Some versions of PHP suffer from memory leaks in long-running scripts.\n" .
            " If your machine gets very slow after many pages (typically more than\n" .
            " 1000) were refreshed, please abort with CTRL-C and resume this script\n" .
            " at the last processed page id using the parameter -s (use -v to display\n" .
            " page ids during refresh). \n\n[Use -x for debugging information]\n\n" .
            "Continue this until all pages were refreshed.\n---\n";

    }

    protected abstract function processPage(Title $title);
    protected abstract function init();

    private function processPages(array $pages)
    {
        foreach ($pages as $page) {

            $page = trim($page);
            if ($this->getOption('v')) {
                print sprintf("(%s) Processing page %s ... \n", $this->num_files, $page);
            }

            $title = Title::newFromText($page);

            if (!is_null($title)) {
                $this->processPage($title);
            }

            $this->num_files++;
        }


    }


    private function processPagesById($start, $end)
    {

        print "Processing all IDs from $start to " . ($end ? "$end" : 'last ID') . " ...\n";
        new SMWDIProperty("_wpg");
        $id = $start;
        while (((!$end) || ($id <= $end)) && ($id > 0)) {
            $title = Title::newFromID($id);
            if ($this->hasOption('v')) {
                print sprintf("(%s) Processing ID %s ... [%s]\n",
                    $this->num_files, $id, !is_null($title) ? $title->getPrefixedText() : "-");
            }
            $id++;
            if (is_null($title)) {
                continue;
            }

            $this->processPage($title);

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
     * Calculates startID of MW-page
     * Reads ID from a file if option 'startidfile' is specified.
     *
     * @return int
     */
    private function getStartId()
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
    private function getEndId($start)
    {
        if ($this->hasOption('e')) { // Note: this might reasonably be larger than the page count
            $end = intval($this->getOption('e'));
        } elseif ($this->hasOption('n')) {
            $end = $start + intval($this->getOption('n'));
        } elseif ($this->hasOption('f')) {
            $title = Title::newFromText($this->getOption('f'));
            $start = $title->getArticleID();
            $end = $title->getArticleID();
        } else {
            $db = wfGetDB(DB_REPLICA);
            $page_table = $db->tableName("page");
            $query = "SELECT MAX(page_id) as maxid FROM $page_table";
            $res = $db->query($query);
            if ($res->numRows($res) > 0) {
                while ($row = $res->fetchObject()) {
                    $end = $row->maxid;
                }
                if ($end == '') {
                    echo "\nThere are no pages. Nothing to do.\n";
                    die();
                }
            }
        }
        return $end;
    }

}