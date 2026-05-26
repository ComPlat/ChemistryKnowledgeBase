<?php

namespace DIQA\ChemExtension\Maintenance;

use DIQA\ChemExtension\Eval\GoldCorrections;
use DIQA\ChemExtension\Eval\GoldSetRepository;

/**
 * Load the required class
 */
if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Applies human-confirmed gold corrections (produced by auditGoldSet.php, then reviewed) back into
 * the gold JSON files. Run after a human has confirmed the findings — this is the only step that
 * changes the gold standard.
 *
 *   php applyGoldCorrections.php --topic Photocatalytic_CO2_conversion           # uses gold_corrections.json
 *   php applyGoldCorrections.php --topic ... --file my_confirmed.json --dry-run
 */
class applyGoldCorrections extends \Maintenance
{
    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Apply confirmed gold corrections back into the gold JSON files');
        $this->addOption('topic', 'Topic directory under eval/', true, true);
        $this->addOption('file', 'Corrections JSON (default: <topic>/gold_corrections.json)', false, true);
        $this->addOption('dry-run', 'Show what would change without writing');
    }

    public function execute()
    {
        $topic = $this->getOption('topic');
        $topicDir = (new GoldSetRepository())->getTopicDir($topic);
        $file = $this->getOption('file', $topicDir . '/gold_corrections.json');

        if (!is_file($file)) {
            $this->fatalError("Corrections file not found: $file (create it from gold_corrections.template.json).");
        }
        $corrections = json_decode(file_get_contents($file), true);
        if (!is_array($corrections)) {
            $this->fatalError("Corrections file is not valid JSON: $file");
        }

        // group by doi
        $byDoi = [];
        foreach ($corrections as $c) {
            if (isset($c['doi'])) {
                $byDoi[$c['doi']][] = $c;
            }
        }

        $appliedTotal = 0;
        foreach ($byDoi as $doi => $list) {
            $goldFile = $topicDir . '/gold/' . $this->safeDoi($doi) . '.json';
            if (!is_file($goldFile)) {
                $this->output("skip (no gold file): $doi\n");
                continue;
            }
            $data = json_decode(file_get_contents($goldFile), true);
            $result = GoldCorrections::apply($data, $list);
            $appliedTotal += $result['applied'];
            foreach ($result['skipped'] as $s) {
                $this->output("  [$doi] skipped: $s\n");
            }
            if ($this->hasOption('dry-run')) {
                $this->output("[dry-run] $doi: would apply {$result['applied']} correction(s)\n");
            } else {
                file_put_contents($goldFile, json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                $this->output("$doi: applied {$result['applied']} correction(s)\n");
            }
        }
        $this->output("\nTotal corrections " . ($this->hasOption('dry-run') ? "(dry-run) " : "") . "applied: $appliedTotal\n");
    }

    private function safeDoi(string $doi): string
    {
        return str_replace(['/', ':', ' '], '_', $doi);
    }
}

$maintClass = applyGoldCorrections::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
