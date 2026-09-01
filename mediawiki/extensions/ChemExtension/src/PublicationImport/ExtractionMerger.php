<?php

namespace DIQA\ChemExtension\PublicationImport;

use DIQA\ChemExtension\Eval\CsvExtractionParser;

/**
 * Deterministic merger for N parallel extraction passes over the same paper.
 *
 * Row-Union + Cell-Majority-Vote: given N wikitext outputs from AIClient::callAI, produces
 * ONE canonical wikitext whose Investigation CSV block contains the union of all experiment
 * rows seen across the passes, with each cell filled by majority vote (with deterministic
 * tie-breakers). This turns o3's per-call variance into stable output — the same PDF/prompt
 * imported multiple times should now yield the same wiki page.
 *
 * The prose sections around the CSV block are taken from the pass with the highest structural
 * completeness (most `== Section ==` headings present); ties broken deterministically by first.
 *
 * @see EnsembleExtractor which orchestrates the N calls and dispatches here.
 * @see CsvExtractionParser::parseRows which is reused verbatim for per-pass CSV parsing.
 */
class ExtractionMerger
{
    /**
     * Row fingerprint = normalised concatenation of these identifying fields. Two rows with the
     * same fingerprint across different passes are treated as the same experiment; rows with
     * different fingerprints are distinct experiments and both kept.
     *
     * Fields chosen to match the ChemWiki template invariants across topics — a photocat
     * experiment is uniquely identified by catalyst + PS + concentrations + solvent + λexc.
     * For topics that omit some (e.g. host-guest), missing fields collapse to empty strings
     * in the fingerprint — still deterministic, just uses whichever fields exist.
     */
    private const FINGERPRINT_FIELDS = [
        'catalyst', 'PS', 'cat conc', 'PS conc',
        'host', 'guest', 'host conc', 'guest conc',
        'solvent-ratio', 'λexc',
    ];

    /**
     * Regex to locate the fenced ```csv block(s) in a wikitext response. Matches the same
     * pattern that ExperimentWikitextImporter uses (`PRE` constant there) so the merger
     * agrees with the downstream importer on what counts as a CSV block.
     */
    private const CSV_BLOCK_PATTERN = '/(```csv)(.*?)(```)/s';

    /**
     * Merge N wikitext outputs from parallel callAI passes into ONE canonical wikitext.
     * Returns the input verbatim when only one pass is given (fast path, no work to do).
     *
     * @param string[] $passes  Wikitext responses from AIClient::callAI
     * @return string Canonical merged wikitext with the same overall structure (prose + one
     *                fenced csv block) as any single pass, ready for the downstream
     *                ExperimentWikitextImporter to consume unchanged.
     */
    public static function merge(array $passes): string
    {
        $passes = array_values(array_filter($passes, fn($p) => is_string($p) && $p !== ''));
        if (count($passes) === 0) {
            return '';
        }
        if (count($passes) === 1) {
            return $passes[0];
        }

        // Parse CSV rows out of each pass (uses the same parser the eval / scorer uses,
        // so behaviour matches whatever the downstream importer would see per single pass).
        $rowsPerPass = array_map([CsvExtractionParser::class, 'parseRows'], $passes);

        // Union of header field names across all passes (usually identical between passes,
        // but be defensive against o3 dropping a column in one pass).
        $headerFields = self::unionHeaders($rowsPerPass);

        // Group rows by fingerprint across the passes. Distinct fingerprints = distinct
        // experiments (kept separate). Same fingerprint = same experiment, needs cell-merge.
        $groups = self::groupByFingerprint($rowsPerPass);

        // Merge each fingerprint group into one row via cell-majority-vote.
        $mergedRows = [];
        foreach ($groups as $group) {
            $mergedRows[] = self::mergeRowGroup($group, $headerFields);
        }

        // Deterministic ordering: sort by fingerprint so the merged output is reproducible
        // even if the input pass order changes.
        usort($mergedRows, fn($a, $b) => self::rowFingerprint($a) <=> self::rowFingerprint($b));

        // Rebuild the CSV block from the merged rows.
        $csvBlock = self::renderCsvBlock($headerFields, $mergedRows);

        // Pick a pass whose prose is most complete, then substitute its csv block with ours.
        $bestPassIdx = self::pickPassForProse($passes);
        // preg_replace with a limit of 1: replace the FIRST csv block in the picked pass with
        // our merged one. Any additional csv blocks in that pass (rare) are left as-is.
        return preg_replace(self::CSV_BLOCK_PATTERN, self::escapeReplacement($csvBlock),
                            $passes[$bestPassIdx], 1) ?? $passes[$bestPassIdx];
    }

    /**
     * Row fingerprint used for grouping: lowercase, whitespace-collapsed concatenation of
     * the identifying fields defined in FINGERPRINT_FIELDS. Missing fields → empty part.
     *
     * @param array<string,string> $row
     */
    public static function rowFingerprint(array $row): string
    {
        $parts = [];
        foreach (self::FINGERPRINT_FIELDS as $f) {
            $v = isset($row[$f]) ? (string)$row[$f] : '';
            $parts[] = self::normalizeValue($v);
        }
        return implode('|', $parts);
    }

    /**
     * Majority-vote for a single cell across passes. Returns:
     *  - '' if no pass had a non-empty value
     *  - the value that appears in the most passes if there is a clear majority
     *  - on ties: longest value first (most specific), then alphabetically first
     *
     * @param string[] $values
     */
    public static function cellMajority(array $values): string
    {
        $nonEmpty = [];
        foreach ($values as $v) {
            $s = trim((string)$v);
            if ($s !== '') {
                $nonEmpty[] = $s;
            }
        }
        if (empty($nonEmpty)) {
            return '';
        }
        $counts = [];
        foreach ($nonEmpty as $v) {
            $counts[$v] = ($counts[$v] ?? 0) + 1;
        }
        arsort($counts);
        $top = reset($counts);
        $tied = [];
        foreach ($counts as $k => $c) {
            if ($c === $top) {
                $tied[] = $k;
            } else {
                break;
            }
        }
        // Tiebreaker: prefer the longest (most specific) value; if still tied, alphabetically first.
        usort($tied, function ($a, $b) {
            $lenCmp = strlen($b) <=> strlen($a);
            if ($lenCmp !== 0) {
                return $lenCmp;
            }
            return $a <=> $b;
        });
        return $tied[0];
    }

    /**
     * Value normalisation for the fingerprint: lowercase, collapse whitespace, trim.
     * Deliberately does NOT touch case/unit inside cell values themselves — only used for
     * grouping so `Fe-1`, ` fe-1 `, `FE-1` all hash to the same fingerprint slot.
     */
    private static function normalizeValue(string $v): string
    {
        return trim(strtolower(preg_replace('/\s+/', ' ', $v) ?? ''));
    }

    /**
     * @param array<int, array<int, array<string,string>>> $rowsPerPass
     * @return string[]
     */
    private static function unionHeaders(array $rowsPerPass): array
    {
        $headers = [];
        foreach ($rowsPerPass as $rows) {
            foreach ($rows as $row) {
                foreach (array_keys($row) as $h) {
                    if (!in_array($h, $headers, true)) {
                        $headers[] = $h;
                    }
                }
            }
        }
        return $headers;
    }

    /**
     * Group rows across all passes by fingerprint.
     *
     * @param array<int, array<int, array<string,string>>> $rowsPerPass
     * @return array<string, array<int, array<string,string>>>  fingerprint → rows in that group
     */
    private static function groupByFingerprint(array $rowsPerPass): array
    {
        $groups = [];
        foreach ($rowsPerPass as $rows) {
            foreach ($rows as $row) {
                $fp = self::rowFingerprint($row);
                $groups[$fp][] = $row;
            }
        }
        return $groups;
    }

    /**
     * Merge one fingerprint group into a single row by cell-majority per column.
     *
     * @param array<int, array<string,string>> $group     rows in the same fingerprint group
     * @param string[]                          $headers   full header set (union)
     * @return array<string,string>
     */
    private static function mergeRowGroup(array $group, array $headers): array
    {
        $merged = [];
        foreach ($headers as $col) {
            $values = array_map(fn($row) => $row[$col] ?? '', $group);
            $merged[$col] = self::cellMajority($values);
        }
        return $merged;
    }

    /**
     * Render the merged rows back into the same fenced ```csv shape the extractor emits.
     *
     * @param string[]                          $headers
     * @param array<int, array<string,string>> $rows
     */
    private static function renderCsvBlock(array $headers, array $rows): string
    {
        $lines = [implode(',', $headers)];
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(fn($h) => $row[$h] ?? '', $headers));
        }
        return "```csv\n" . implode("\n", $lines) . "\n```";
    }

    /**
     * Pick the pass whose prose is most complete — the one that emitted the most `== Section ==`
     * headings. On ties: prefer the first pass in argument order (deterministic).
     *
     * @param string[] $passes
     */
    private static function pickPassForProse(array $passes): int
    {
        $bestIdx = 0;
        $bestCount = -1;
        foreach ($passes as $i => $p) {
            $count = preg_match_all('/^==\s*[^=]+\s*==\s*$/m', $p) ?: 0;
            if ($count > $bestCount) {
                $bestCount = $count;
                $bestIdx = $i;
            }
        }
        return $bestIdx;
    }

    /**
     * preg_replace treats $ and \ in the replacement as backreferences — the merged CSV
     * text could legitimately contain those, so we escape them here to survive the substitution.
     */
    private static function escapeReplacement(string $s): string
    {
        return strtr($s, ['\\' => '\\\\', '$' => '\\$']);
    }
}
