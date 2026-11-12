<?php

namespace DIQA\ChemExtension\Maintenance;

use DIQA\ChemExtension\Eval\GoldSetRepository;
use DIQA\ChemExtension\PublicationImport\AIProviderConfig;

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
        $this->output("== AI provider ==\n");
        $keyOk = false;
        try {
            $config = AIProviderConfig::fromGlobals();
            $keyOk = !$config->requiresApiKey() || $config->getApiKey() !== null;
            $this->output("  provider: " . $config->getProvider() . " (\$wgCEAIProvider)\n");
            $this->output("  endpoint: " . $config->getBaseUrl() . "\n");
            $this->output("  model:    " . $config->getModel() . "\n");
            $this->output("  PDF input: " . $config->getPdfInputMode() . "\n");
            $this->output("  API key:  " . ($keyOk ? "set ✓" : "MISSING ✗ (required)") . "\n");
            $this->output("  embedding model: " . ($GLOBALS['wgCEAIEmbeddingModel'] ?? $GLOBALS['wgOpenAIEmbeddingModel'] ?? "(provider default)") . "\n\n");
        } catch (\InvalidArgumentException $e) {
            $this->output("  configuration error: " . $e->getMessage() . "\n\n");
        }

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

        $this->output("\n== Verdict ==\n");
        if ($keyOk && $totalRunnable > 0) {
            $this->output("READY ✓ — $totalRunnable publication(s) with PDF. Start with:\n");
            $this->output("  php maintenance/optimizeExtractionPrompt.php --topic <Topic> --iterations 5 --structured --critic 0.6\n");
        } elseif (!$keyOk) {
            $this->output("Configure the AI provider and its API key in LocalSettings.php (see AIProviderConfig), then you can start.\n");
        } else {
            $this->output("Add PDFs into eval/<Topic>/pdfs/ (see PDFS_TO_DOWNLOAD.md), then start.\n");
        }
    }
}

$maintClass = checkEvalSetup::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
