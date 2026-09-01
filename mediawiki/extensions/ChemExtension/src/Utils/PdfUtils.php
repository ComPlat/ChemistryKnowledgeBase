<?php

namespace DIQA\ChemExtension\Utils;

use FilesystemIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class PdfUtils {

    /**
     * Determines if a given file is a PDF file.
     *
     * Checks the file's magic bytes (file signature) to reliably
     * detect PDF files, regardless of file extension.
     *
     * @param string $filePath The path to the file to check.
     * @return bool True if the file is a PDF, false otherwise.
     * @throws \InvalidArgumentException If the file path is empty.
     * @throws \RuntimeException If the file does not exist or is not readable.
     */
    static function isPdfFile(string $filePath): bool
    {
        if ($filePath === '') {
            return false;
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            return false;
        }

        // PDF files start with the magic bytes "%PDF" (hex: 25 50 44 46)
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return false;
        }

        $header = fread($handle, 4);
        fclose($handle);

        if ($header === false || strlen($header) < 4) {
            return false;
        }

        return $header === '%PDF';
    }

    private static function checkPDFPrerequisites(): string {
        global $wgChemPubStoreDir;
        if (!isset($wgChemPubStoreDir)) {
            $wgChemPubStoreDir = sys_get_temp_dir();
        }
        if (!file_exists($wgChemPubStoreDir)) {
            mkdir($wgChemPubStoreDir);
        }
        if (!is_writable($wgChemPubStoreDir)) {
            throw new \Exception("temporary uploadfolder $wgChemPubStoreDir must be writeable. Please configure.");
        }
        return $wgChemPubStoreDir;
    }

    public static function getPublicationPDFDirectory(string $doi): string {
        $wgChemPubStoreDir = self::checkPDFPrerequisites();
        return $wgChemPubStoreDir . "/" . md5($doi) . '.pdf';
    }

    public static function getPublicationPDFs(string $doi): array
    {
        $wgChemPubStoreDir = self::checkPDFPrerequisites();

        $file = $wgChemPubStoreDir . "/" . md5($doi) . '.pdf';
        if (file_exists($file) && !is_dir($file)) {
            return [ $file ];
        }
        if (!is_dir($file)) {
            return [];
        }
        return self::getFiles($wgChemPubStoreDir . "/" . md5($doi). '.pdf');
    }

    public static function isDirectoryNotEmpty(string $directory): bool
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException("Not a valid directory: {$directory}");
        }

        $iterator = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

        return $iterator->valid();
    }

    public static function getFiles(string $directory, bool $recursive = false): array
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException("Not a valid directory: {$directory}");
        }

        $files = [];

        if ($recursive) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile()) {
                    $files[] = $fileInfo->getPathname();
                }
            }
        } else {
            $iterator = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile()) {
                    $files[] = $fileInfo->getPathname();
                }
            }
        }

        return $files;
    }

    private static function storeDir(): string
    {
        global $wgChemPubStoreDir;
        if (!isset($wgChemPubStoreDir)) {
            $wgChemPubStoreDir = sys_get_temp_dir();
        }
        return $wgChemPubStoreDir;
    }

    /**
     * Stores a supplementary-information PDF for a DOI next to the main PDF.
     */
    public static function savePublicationSI(string $doi, int $index, string $content): void
    {
        file_put_contents(self::storeDir() . "/" . md5($doi) . "_si{$index}.pdf", $content);
    }

    /**
     * @return string[] paths of stored supplementary PDFs for the DOI (may be empty)
     */
    public static function publicationSIPDFs(string $doi): array
    {
        return glob(self::storeDir() . "/" . md5($doi) . "_si*.pdf") ?: [];
    }

    /**
     * @return string[] the main PDF (if present) followed by any supplementary PDFs.
     */
    public static function publicationAllPDFs(string $doi): array
    {
        $all = [];
        $main = self::publicationPDF($doi);
        if (is_file($main)) {
            $all[] = $main;
        }
        return array_merge($all, self::publicationSIPDFs($doi));
    }
}
