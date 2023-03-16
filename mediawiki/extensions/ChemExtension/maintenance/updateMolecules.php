<?php

use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientImpl;
use DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientMock;
use DIQA\ChemExtension\Utils\WikiTools;
use MediaWiki\MediaWikiServices;

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

class updateMolecules extends Maintenance
{

    private $linkCache;
    private $writeToStartidfile;

    public function __construct()
    {
        parent::__construct();
        $this->mDescription = "Updates molecules if they dont have mass and formula";
        $this->addOption('v', 'Verbose mode', false, false);
        $this->addOption('dryRun', 'Dry run', false, false);
        $this->addOption('d', 'Delay every 100 pages (miliseconds)', false, true);
        $this->addOption('x', 'Debug mode', false, false);
        $this->addOption('p', 'Page title(s), separated by ","', false, true);
        $this->addOption('s', 'Start-ID', false, true);
        $this->addOption('e', 'End-ID', false, true);
        $this->addOption('n', 'Number of IDs from Start-ID', false, true);
        $this->addOption('f', 'End-ID by Pagename', false, true);
        $this->addOption('startidfile', 'File containing ID to start processing and saves last processed ID to this file', false, true);
    }

    public function execute()
    {

        // when indexing everything, dependent pages do not need special treatment
        global $fsUpdateOnlyCurrentArticle;
        $fsUpdateOnlyCurrentArticle = true;

        $this->linkCache = MediaWikiServices::getInstance()->getLinkCache();
        $this->num_files = 0;
        $this->printDocHeader();

        if (!$this->hasOption('p')) {
            $startId = $this->getStartId();
            $endId = $this->getEndId($startId);
            $this->refreshPagesByIds($startId, $endId);
        } else {
            $pages = explode(',', $this->getOption('p'));
            $this->refreshPages($pages);
        }

        print "\n{$this->num_files} IDs refreshed.\n";
    }

    /**
     * Print Documatation header
     */
    private function printDocHeader()
    {
        print "Refreshing all semantic data in the SOLR server!\n---\n" .
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
     */
    private function refreshPagesByIds($start, $end)
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

            $this->updateMoleculeData($title);

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
     * Refresh given pages.
     *
     * @param array of string $pages Page titles
     */
    private function refreshPages($pages)
    {
        print "Refreshing specified pages!\n\n";

        foreach ($pages as $page) {

            $page = trim($page);
            if ($this->getOption('v')) {
                print sprintf("(%s) Processing page %s ... \n", $this->num_files, $page);
            }

            $title = Title::newFromText($page);

            if (!is_null($title) && $title->getNamespace() === NS_MOLECULE) {
                $this->updateMoleculeData($title);
            }

            $this->num_files++;
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
            if ($db->numRows($res) > 0) {
                while ($row = $db->fetchObject($res)) {
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

    /**
     * Updates the molecule page and adds mass and formula
     */
    private function updateMoleculeData(Title $title)
    {
        global $wgCEUseMoleculeRGroupsClientMock;
        $rGroupClient = $wgCEUseMoleculeRGroupsClientMock ?
            new \DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientMock()
            : new \DIQA\ChemExtension\MoleculeRGroupBuilder\MoleculeRGroupServiceClientImpl();

        $text = WikiTools::getText($title);
        $te = new \DIQA\ChemExtension\Utils\TemplateEditor($text);
        if ($te->exists('Molecule')) {
            $params = $te->getTemplateParams('Molecule');
            $mass = $params['molecularFormula'] ?? '';
            $formula = $params['molecularFormula'] ?? '';
            $molOrRxn = $params['molOrRxn'] ?? '';
            if (($mass == '' || $formula == '') && $molOrRxn != '') {
                try {
                    $metadata = $rGroupClient->getMetadata($molOrRxn);
                    if ($metadata['molecularMass'] != '') {
                        $params['molecularMass'] = $metadata['molecularMass'];
                    }
                    if ($metadata['molecularFormula'] != '') {
                        $params['molecularFormula'] = \DIQA\ChemExtension\Utils\HtmlTools::formatSumFormula($metadata['molecularFormula']);
                    }
                    $te->replaceTemplateParameters('Molecule', $params);
                    if (!$this->hasOption('dryRun')) {
                        WikiTools::doEditContent($title, $te->getWikiText(), "auto-updated mass/formula");
                    }
                    print "\tSave page: " . $title->getPrefixedText() . "\n";

                } catch(\Exception $e) {
                    print "\tProblem on page: " . $title->getPrefixedText(). "\n";
                }
            }
        }
    }

}

$maintClass = "updateMolecules";
require_once RUN_MAINTENANCE_IF_MAIN;
