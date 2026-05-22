<?php

namespace DIQA\ChemExtension\Eval;

/**
 * Parses the CSV experiment table out of an AI extraction response.
 *
 * Mirrors the header handling of {@see \DIQA\ChemExtension\PublicationImport\ExperimentWikitextImporter}
 * so the evaluation interprets the columns exactly like the real import would: it looks for a
 * fenced ```csv block (or a <pre> block) and strips bracketed unit hints from the header, e.g.
 * "cat conc [µM]" -> "cat conc".
 *
 * This is read-only — unlike the importer it does not create wiki pages or look up molecules;
 * it just returns the structured rows so they can be scored against the gold set.
 */
class CsvExtractionParser
{
    private const PRE = '/(<pre>|```csv)(.*?)(<\/pre>|```)/s';

    /**
     * Extracts all CSV tables from the given text.
     *
     * @param string $text AI response
     * @return array<int, array{header: string[], rows: array<int, array<string,string>>}>
     */
    public static function parse(string $text): array
    {
        preg_match_all(self::PRE, $text, $matches);

        $tables = [];
        foreach ($matches[2] as $block) {
            $lines = array_values(array_filter(
                array_map('trim', explode("\n", trim($block))),
                fn($l) => $l !== ''
            ));
            if (count($lines) < 1) {
                continue;
            }
            $header = self::cleanHeader(array_shift($lines));
            $rows = [];
            foreach ($lines as $line) {
                $columns = array_map('trim', explode(',', $line));
                while (count($header) > count($columns)) {
                    $columns[] = '';
                }
                $columns = array_slice($columns, 0, count($header));
                $rows[] = array_combine($header, $columns);
            }
            $tables[] = ['header' => $header, 'rows' => $rows];
        }
        return $tables;
    }

    /**
     * Returns the rows of the first/largest CSV table, or [] if none was found.
     *
     * @param string $text
     * @return array<int, array<string,string>>
     */
    public static function parseRows(string $text): array
    {
        $tables = self::parse($text);
        if (empty($tables)) {
            return [];
        }
        // pick the table with the most rows
        usort($tables, fn($a, $b) => count($b['rows']) <=> count($a['rows']));
        return $tables[0]['rows'];
    }

    /**
     * @param string $header
     * @return string[]
     */
    private static function cleanHeader(string $header): array
    {
        return array_map(
            fn($e) => trim(preg_replace('/\[[^]]*\]/', '', $e)),
            explode(',', $header)
        );
    }
}
