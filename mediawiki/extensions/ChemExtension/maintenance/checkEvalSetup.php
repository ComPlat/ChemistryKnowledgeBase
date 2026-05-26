<?php

namespace DIQA\ChemExtension\Maintenance;

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
 * Pre-flight check: reports whether everything is ready to run the optimization loop — the OpenAI
 * key, and per topic how many gold publications have their PDF present. Needs no API call.
 *
 *   php checkEvalSetup.php
 */
class checkEvalSetup extends \Maintenance
{
    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Pre-flight check for the extraction evaluation loop');
    }

    public function execute()
    {
        global $wgOpenAIKey, $wgOpenAIModel, $wgOpenAIEmbeddingModel;

        $this->output("== Config ==\n");
        $this->output("  \$wgOpenAIKey: " . (isset($wgOpenAIKey) && $wgOpenAIKey ? "set ✓" : "MISSING ✗ (required)") . "\n");
        $this->output("  \$wgOpenAIModel: " . ($wgOpenAIModel ?? "(default o3)") . "\n");
        $this->output("  \$wgOpenAIEmbeddingModel: " . ($wgOpenAIEmbeddingModel ?? "(default text-embedding-3-small)") . "\n\n");

        $goldRepo = new GoldSetRepository();
        $topics = $goldRepo->getTopics();
        if (empty($topics)) {
            $this->output("No gold set found. Build it with buildGoldSetFromXml.php.\n");
            return;
        }

        $this->output("== Gold set & PDFs ==\n");
        $totalRunnable = 0;
        foreach ($topics as $topic) {
            $entries = $goldRepo->loadTopic($topic);
            $withPdf = 0;
            foreach ($entries as $e) {
                if (!empty(array_filter($e['pdfPaths'], 'is_file'))) {
                    $withPdf++;
                }
            }
            $totalRunnable += $withPdf;
            $status = $withPdf > 0 ? "runnable" : "no PDFs yet";
            $this->output(sprintf("  %-32s gold=%-3d  with PDF=%-3d  -> %s\n", $topic, count($entries), $withPdf, $status));
        }

        $keyOk = isset($wgOpenAIKey) && $wgOpenAIKey;
        $this->output("\n== Verdict ==\n");
        if ($keyOk && $totalRunnable > 0) {
            $this->output("READY ✓ — $totalRunnable publication(s) with PDF. Start with:\n");
            $this->output("  php maintenance/optimizeExtractionPrompt.php --topic <Topic> --iterations 5 --structured --critic 0.6\n");
        } elseif (!$keyOk) {
            $this->output("Set \$wgOpenAIKey in LocalSettings.php, then you can start.\n");
        } else {
            $this->output("Add PDFs into eval/<Topic>/pdfs/ (see PDFS_TO_DOWNLOAD.md), then start.\n");
        }
    }
}

$maintClass = checkEvalSetup::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
