<?php

namespace DIQA\ChemExtension\PublicationImport;

use RuntimeException;

/**
 * Converts PDFs to plain text with poppler's `pdftotext`, for LLM backends that cannot read
 * PDF files natively (vLLM/Ollama-served open-weight models).
 *
 * `-layout` keeps table columns aligned, which matters for extracting experiment tables.
 * The binary is taken from $wgPdftoText (shared with the PdfHandler extension) and defaults
 * to `pdftotext` on the PATH. The process is spawned without a shell, so file names are never
 * interpreted.
 */
class PdfTextExtractor
{
    private string $binary;

    public function __construct(?string $binary = null)
    {
        $this->binary = $binary ?? ($GLOBALS['wgPdftoText'] ?? 'pdftotext');
    }

    /**
     * @throws RuntimeException if the file is missing, pdftotext fails or yields no text
     *                          (e.g. a scanned PDF without a text layer)
     */
    public function extract(string $pdfPath): string
    {
        if (!is_file($pdfPath)) {
            throw new RuntimeException("PDF not found: $pdfPath");
        }
        $cmd = [$this->binary, '-layout', '-enc', 'UTF-8', '-q', $pdfPath, '-'];
        $process = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException("Could not start '{$this->binary}'. Is poppler-utils installed?");
        }
        $text = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException(sprintf("pdftotext failed for %s (exit %d): %s",
                $pdfPath, $exitCode, trim((string)$stderr)));
        }
        $text = self::normalize((string)$text);
        if ($text === '') {
            throw new RuntimeException("PDF has no text layer (scanned document?): $pdfPath");
        }
        return $text;
    }

    /**
     * Drops form feeds and trailing whitespace and collapses runs of blank lines; this
     * reduces token count noticeably for layout-mode output without losing structure.
     */
    public static function normalize(string $text): string
    {
        $text = str_replace(["\r\n", "\f"], ["\n", "\n"], $text);
        $text = preg_replace('/[ \t]+$/m', '', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        return trim($text);
    }
}
