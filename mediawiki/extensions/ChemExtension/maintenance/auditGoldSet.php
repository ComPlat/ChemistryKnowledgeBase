<?php

namespace DIQA\ChemExtension\Maintenance;

use DIQA\ChemExtension\Eval\GoldAuditor;
use DIQA\ChemExtension\Eval\GoldSetRepository;
use DIQA\ChemExtension\PublicationImport\AIClient;

/**
 * Load the required class
 */
if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Audits the gold standard of a topic against the source PDFs and writes a human review of
 * suspected gold errors (with evidence). Nothing is changed automatically — confirm the findings,
 * then apply them with applyGoldCorrections.php. This lets the gold set co-evolve with the prompt.
 *
 *   php auditGoldSet.php --topic Photocatalytic_CO2_conversion --threshold 0.7
 */
class auditGoldSet extends \Maintenance
{
    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Audit a topic gold set against the source PDFs; report suspected gold errors');
        $this->addOption('topic', 'Topic directory under eval/', true, true);
        $this->addOption('threshold', 'Min confidence to report a finding (default 0.7)', false, true);
    }

    public function execute()
    {
        $topic = $this->getOption('topic');
        $threshold = (float) $this->getOption('threshold', 0.7);

        $goldRepo = new GoldSetRepository();
        $entries = $goldRepo->loadTopic($topic);
        if (empty($entries)) {
            $this->fatalError("No gold set for topic '$topic'.");
        }

        $client = new AIClient();
        $auditor = new GoldAuditor($client, $threshold);

        $all = [];   // doi => findings[]
        foreach ($entries as $entry) {
            $pdfs = array_values(array_filter($entry['pdfPaths'], 'is_file'));
            if (empty($pdfs) || empty($entry['experiments'])) {
                continue;
            }
            $this->output("auditing: {$entry['doi']}\n");
            $fileIds = $client->uploadFiles($pdfs);
            if (empty($fileIds)) {
                continue;
            }
            try {
                $findings = $auditor->auditWithFiles($fileIds, $entry['experiments']);
            } finally {
                $client->deleteFiles($fileIds);
            }
            if (!empty($findings)) {
                $all[$entry['doi']] = $findings;
                $this->output("  " . count($findings) . " suspected gold error(s)\n");
            }
        }

        $this->writeReport($goldRepo->getTopicDir($topic), $all);
    }

    private function writeReport(string $topicDir, array $all): void
    {
        $reviewMd = "# Gold-standard review — suspected errors\n\n"
            . "Confirm each finding against the source, then keep the confirmed ones in "
            . "`gold_corrections.json` and run `applyGoldCorrections.php`.\n\n";
        $template = [];

        $total = 0;
        foreach ($all as $doi => $findings) {
            $reviewMd .= "## $doi\n\n| row | field | gold | suggested | conf | evidence |\n|---|---|---|---|---|---|\n";
            foreach ($findings as $f) {
                $total++;
                $reviewMd .= sprintf(
                    "| %d | %s | %s | %s | %.2f | %s |\n",
                    $f['rowIndex'], $f['field'], $f['goldValue'], $f['suggestedValue'], $f['confidence'],
                    str_replace('|', '\\|', mb_substr($f['evidence'], 0, 160))
                );
                $template[] = [
                    'doi' => $doi,
                    'row' => $f['rowIndex'],
                    'field' => $f['field'],
                    'value' => $f['suggestedValue'],
                    '_gold' => $f['goldValue'],
                    '_confidence' => $f['confidence'],
                    '_evidence' => $f['evidence'],
                ];
            }
            $reviewMd .= "\n";
        }
        if ($total === 0) {
            $reviewMd .= "_No suspected gold errors above the threshold._\n";
        }

        file_put_contents($topicDir . '/gold_review.md', $reviewMd);
        file_put_contents(
            $topicDir . '/gold_corrections.template.json',
            json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
        $this->output("\nWrote $total finding(s) to:\n  $topicDir/gold_review.md\n  $topicDir/gold_corrections.template.json\n");
        $this->output("Review, then copy the confirmed entries into gold_corrections.json and run applyGoldCorrections.php.\n");
    }
}

$maintClass = auditGoldSet::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
