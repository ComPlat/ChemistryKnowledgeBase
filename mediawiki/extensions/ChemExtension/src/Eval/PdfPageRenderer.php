<?php

namespace DIQA\ChemExtension\Eval;

use DIQA\ChemExtension\Utils\LoggerUtils;

/**
 * Renders the first pages of a PDF to PNG images for vision input. Tables in chemistry papers are
 * read far more reliably from rendered pages than from the PDF text stream, so attaching page
 * images alongside the document tends to improve table extraction.
 *
 * Uses poppler's `pdftoppm` if available; degrades gracefully (returns []) when the tool or shell
 * access is missing, so the pipeline keeps working without vision.
 */
class PdfPageRenderer
{
    private LoggerUtils $logger;
    private int $dpi;

    public function __construct(int $dpi = 150)
    {
        $this->logger = new LoggerUtils('PdfPageRenderer', 'ChemExtension');
        $this->dpi = $dpi;
    }

    /**
     * Renders up to $maxPages pages of the PDF to PNG files in a temp directory.
     *
     * @return string[] absolute paths of the generated images (empty if rendering is unavailable)
     */
    public function renderPages(string $pdfPath, int $maxPages = 6): array
    {
        if (!is_file($pdfPath) || !$this->isAvailable()) {
            return [];
        }
        $outDir = sys_get_temp_dir() . '/chemext-pages-' . uniqid();
        if (!mkdir($outDir) && !is_dir($outDir)) {
            return [];
        }
        $prefix = $outDir . '/page';
        $cmd = sprintf(
            'pdftoppm -png -r %d -f 1 -l %d %s %s 2>/dev/null',
            $this->dpi,
            $maxPages,
            escapeshellarg($pdfPath),
            escapeshellarg($prefix)
        );
        @exec($cmd, $output, $exitCode);
        if ($exitCode !== 0) {
            $this->logger->warn("pdftoppm failed for $pdfPath (exit $exitCode)");
            return [];
        }
        $images = glob($prefix . '*.png') ?: [];
        sort($images);
        return $images;
    }

    public function cleanup(array $images): void
    {
        $dirs = [];
        foreach ($images as $image) {
            if (is_file($image)) {
                @unlink($image);
                $dirs[dirname($image)] = true;
            }
        }
        foreach (array_keys($dirs) as $dir) {
            @rmdir($dir);
        }
    }

    private function isAvailable(): bool
    {
        if (!function_exists('exec')) {
            return false;
        }
        @exec('pdftoppm -h 2>/dev/null', $out, $code);
        // pdftoppm -h prints usage and returns non-zero on some builds; treat "command found" as ok
        return $code === 0 || !empty($out) || $this->commandExists('pdftoppm');
    }

    private function commandExists(string $cmd): bool
    {
        @exec('command -v ' . escapeshellarg($cmd) . ' 2>/dev/null', $out, $code);
        return $code === 0 && !empty($out);
    }
}
