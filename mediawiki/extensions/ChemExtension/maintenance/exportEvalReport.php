<?php

namespace DIQA\ChemExtension\Maintenance;

use DIQA\ChemExtension\Eval\GoldSetRepository;
use DIQA\ChemExtension\Eval\MetricsReport;

/**
 * Load the required class
 */
if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Exports paper-ready figures and tables (convergence/trends) from the recorded evaluation runs.
 * Needs no API key — it only reads eval/<topic>/runs/.
 *
 *   php exportEvalReport.php                 # all topics that have runs
 *   php exportEvalReport.php --topic Host_Guest_interaction
 */
class exportEvalReport extends \Maintenance
{
    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Generate paper-ready metric reports (CSV, SVG charts, LaTeX, Markdown) from eval runs');
        $this->addOption('topic', 'Only this topic (default: all topics with recorded runs)', false, true);
    }

    public function execute()
    {
        $report = new MetricsReport();
        $goldRepo = new GoldSetRepository();

        $topics = $this->getOption('topic') !== null
            ? [$this->getOption('topic')]
            : $goldRepo->getTopics();

        if (empty($topics)) {
            $this->output("No topics found.\n");
            return;
        }

        $done = [];
        foreach ($topics as $topic) {
            try {
                $files = $report->generateForTopic($topic);
                $done[] = $topic;
                $this->output("[$topic] wrote:\n  " . implode("\n  ", $files) . "\n");
            } catch (\Throwable $e) {
                $this->output("[$topic] skipped: " . $e->getMessage() . "\n");
            }
        }

        // cross-topic comparison figure when more than one topic has runs
        if (count($done) > 1) {
            $files = $report->generateComparison($done);
            if (!empty($files)) {
                $this->output("[comparison] wrote:\n  " . implode("\n  ", $files) . "\n");
            }
        }
    }
}

$maintClass = exportEvalReport::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
