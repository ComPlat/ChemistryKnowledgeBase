<?php
declare(strict_types=1);

namespace DIQA\FacetedSearch2\TextExtractors;

use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;

class XLSExtractor
{
    /**
     * Extract textual content from an Excel file.
     *
     * @param string $path
     * @return string Map of sheet title => extracted text
     * @throws PhpSpreadsheetException
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function extractXlsxText(string $path): string
    {
        if (!is_readable($path)) {
            throw new Exception("Cannot read file: $path");
        }

        // Auto-detect reader (Xlsx, Xls, Ods, Csv, ...)
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);   // skip styles -> much faster, lower memory
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($path);

        $result = [];
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $result[$sheet->getTitle()] = $this->extractSheetText($sheet);
        }

        // Help GC for large workbooks
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return join("\n", array_values($result));
    }


    private function extractSheetText(Worksheet $sheet): string
    {
        $lines = [];
        foreach ($sheet->getRowIterator() as $row) {
            try {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(true);
            } catch (PhpSpreadsheetException $e) {
                continue;
            }

            $cells = [];
            foreach ($cellIterator as $cell) {
                // getFormattedValue() applies number/date formats -> human-readable text
                $value = $cell->getFormattedValue();
                if ($value !== '' && $value !== null) {
                    $cells[] = (string)$value;
                }
            }
            if ($cells !== []) {
                $lines[] = implode("\t", $cells);
            }
        }
        return implode("\n", $lines);
    }

}