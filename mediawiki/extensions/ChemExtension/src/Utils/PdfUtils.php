<?php

namespace DIQA\ChemExtension\Utils;

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
            throw new \InvalidArgumentException('File path must not be empty.');
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException(
                sprintf('File does not exist or is not readable: %s', $filePath)
            );
        }

        // PDF files start with the magic bytes "%PDF" (hex: 25 50 44 46)
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            throw new \RuntimeException(
                sprintf('Unable to open file: %s', $filePath)
            );
        }

        $header = fread($handle, 4);
        fclose($handle);

        if ($header === false || strlen($header) < 4) {
            return false;
        }

        return $header === '%PDF';
    }

    public static function savePublicationPDF(string $doi, string $content): void
    {
        global $wgChemPubStoreDir;
        if (!isset($wgChemPubStoreDir)) {
            $wgChemPubStoreDir = sys_get_temp_dir();
        }
        file_put_contents($wgChemPubStoreDir . "/" . md5($doi) . '.pdf', $content);
    }

    public static function publicationPDF(string $doi): string
    {
        global $wgChemPubStoreDir;
        if (!isset($wgChemPubStoreDir)) {
            $wgChemPubStoreDir = sys_get_temp_dir();
        }
        if (!is_dir($wgChemPubStoreDir . "/" . md5($doi) . '.pdf')) {
            return $wgChemPubStoreDir . "/" . md5($doi) . '.pdf';
        }
        return self::getFirstFileInDirectory($wgChemPubStoreDir . "/" . md5($doi). '.pdf');
    }

    private static function getFirstFileInDirectory(string $directoryPath): ?string
    {
        if (!is_dir($directoryPath)) {
            return null;
        }

        $files = scandir($directoryPath);

        foreach ($files as $file) {
            $fullPath = $directoryPath . DIRECTORY_SEPARATOR . $file;
            if (is_file($fullPath)) {
                return $fullPath;
            }
        }

        return null;
    }
}
