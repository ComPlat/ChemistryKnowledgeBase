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

    public static function publicationPDF(string $doi): array
    {
        global $wgChemPubStoreDir;
        if (!isset($wgChemPubStoreDir)) {
            $wgChemPubStoreDir = sys_get_temp_dir();
        }
        if (!is_dir($wgChemPubStoreDir . "/" . md5($doi) . '.pdf')) {
            return [ $wgChemPubStoreDir . "/" . md5($doi) . '.pdf' ];
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
}
