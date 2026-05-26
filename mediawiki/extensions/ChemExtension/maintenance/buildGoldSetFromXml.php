<?php

namespace DIQA\ChemExtension\Maintenance;

use DIQA\ChemExtension\Eval\GoldSetFromXml;
use DIQA\ChemExtension\PublicationSearch\UnpaywallAPI;

/**
 * Load the required class
 */
if (getenv('MW_INSTALL_PATH') !== false) {
    require_once getenv('MW_INSTALL_PATH') . '/maintenance/Maintenance.php';
} else {
    require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * Builds the evaluation gold set from a MediaWiki XML export of the curated publication and
 * investigation pages, and (optionally) fetches the open-access PDFs by DOI.
 *
 *   php buildGoldSetFromXml.php --publications publications.xml --investigations investigations.xml --fetch-pdfs
 *
 * Writes eval/<Topic>/gold/<doi>.json (the reference extractions) and, with --fetch-pdfs, the
 * freely available PDFs into eval/<Topic>/pdfs/. DOIs without an open-access PDF are listed so they
 * can be added manually (e.g. via institutional access).
 */
class buildGoldSetFromXml extends \Maintenance
{
    public function __construct()
    {
        parent::__construct();
        $this->addDescription('Build the eval gold set from publications.xml + investigations.xml');
        $this->addOption('publications', 'Path to the publications XML export', true, true);
        $this->addOption('investigations', 'Path to the investigations XML export', true, true);
        $this->addOption('out', 'Eval base directory (default: <extension>/eval)', false, true);
        $this->addOption('fetch-pdfs', 'Also download open-access PDFs by DOI via Unpaywall');
        $this->addOption('overwrite', 'Overwrite existing gold JSON files');
    }

    public function execute()
    {
        $pubFile = $this->getOption('publications');
        $invFile = $this->getOption('investigations');
        foreach ([$pubFile, $invFile] as $f) {
            if (!is_file($f)) {
                $this->fatalError("File not found: $f");
            }
        }
        $base = $this->getOption('out', dirname(__DIR__) . '/eval');

        $entries = GoldSetFromXml::build(file_get_contents($pubFile), file_get_contents($invFile));
        if (empty($entries)) {
            $this->fatalError("No gold entries could be built — check the XML exports.");
        }

        $unpaywall = $this->hasOption('fetch-pdfs') ? new UnpaywallAPI() : null;
        $written = 0;
        $pdfOk = 0;
        $missingPdf = [];
        $perTopic = [];

        foreach ($entries as $entry) {
            $topicDir = $entry['topic'];
            $safeDoi = $this->safeDoi($entry['doi']);
            $goldDir = "$base/$topicDir/gold";
            $pdfDir = "$base/$topicDir/pdfs";
            $this->ensureDir($goldDir);
            $this->ensureDir($pdfDir);

            $goldFile = "$goldDir/$safeDoi.json";
            if (is_file($goldFile) && !$this->hasOption('overwrite')) {
                $this->output("skip (exists): $goldFile\n");
            } else {
                file_put_contents($goldFile, json_encode([
                    'doi' => $entry['doi'],
                    'topic' => str_replace('_', ' ', $topicDir),
                    'pdf' => "pdfs/$safeDoi.pdf",
                    'experiments' => $entry['experiments'],
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                $written++;
            }
            $perTopic[$topicDir] = ($perTopic[$topicDir] ?? 0) + 1;

            $pdfPath = "$pdfDir/$safeDoi.pdf";
            if (is_file($pdfPath)) {
                $pdfOk++;
                continue;
            }
            if ($unpaywall !== null) {
                $url = $unpaywall->findOpenAccessPdfUrl($entry['doi']);
                if ($url !== null) {
                    $content = @file_get_contents($url);
                    if ($content !== false && str_starts_with($content, '%PDF')) {
                        file_put_contents($pdfPath, $content);
                        $pdfOk++;
                        $this->output("pdf ok: {$entry['doi']}\n");
                        continue;
                    }
                }
            }
            $missingPdf[] = $entry['doi'];
        }

        $this->output("\n=== Gold set built ===\n");
        foreach ($perTopic as $topic => $n) {
            $this->output("  $topic: $n publications\n");
        }
        $this->output("Gold JSON written: $written, PDFs present: $pdfOk, missing PDFs: " . count($missingPdf) . "\n");
        if (!empty($missingPdf)) {
            $this->output("\nMissing PDFs (add manually into the topic's pdfs/ folder, named <doi-with-slashes-as-underscores>.pdf):\n");
            foreach ($missingPdf as $doi) {
                $this->output("  - $doi  ->  " . $this->safeDoi($doi) . ".pdf\n");
            }
        }
    }

    private function safeDoi(string $doi): string
    {
        return str_replace(['/', ':', ' '], '_', $doi);
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
}

$maintClass = buildGoldSetFromXml::class;
require_once(RUN_MAINTENANCE_IF_MAIN);
