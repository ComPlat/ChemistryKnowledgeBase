<?php

namespace DIQA\ChemExtension\Eval;

/**
 * Parses a structured-output JSON response (see {@see TopicSchema}) into the same row shape the
 * rest of the evaluation uses. Counterpart to {@see CsvExtractionParser} for the structured path.
 */
class StructuredExtractionParser
{
    /**
     * @return array{rows: array<int, array<string,string>>, summary: string}
     */
    public static function parse(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return ['rows' => [], 'summary' => ''];
        }

        $summary = '';
        if (isset($data['summary']) && is_string($data['summary'])) {
            $summary = $data['summary'];
        }

        $rows = [];
        foreach (($data['experiments'] ?? []) as $experiment) {
            if (!is_array($experiment)) {
                continue;
            }
            $row = [];
            foreach ($experiment as $key => $value) {
                $row[$key] = $value === null ? '' : (is_scalar($value) ? (string) $value : '');
            }
            $rows[] = $row;
        }

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * @return array<int, array<string,string>>
     */
    public static function parseRows(string $json): array
    {
        return self::parse($json)['rows'];
    }
}
