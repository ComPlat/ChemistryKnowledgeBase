<?php

namespace DIQA\ChemExtension\Eval;

/**
 * Scores an AI extraction against a curated gold extraction for one publication.
 *
 * The metric is a structured, field-level comparison (not text similarity): every non-empty
 * gold cell is a thing the model should have reproduced. We greedily match extracted experiment
 * rows to gold rows, then compare cell by cell:
 *   - numeric cells (e.g. Ka, Kd, ΔG, TON, faradaic efficiency, concentrations) match within a
 *     relative tolerance,
 *   - all other cells match by normalized string equality.
 *
 * From the cell-level true positives we derive precision / recall / F1, plus a structured error
 * report (which fields are systematically missed or wrong) that {@see PromptOptimizer} feeds
 * back to the model.
 */
class ExtractionScorer
{
    /** relative tolerance for numeric comparison (10%) */
    private float $numericTolerance;
    /** @var array<string,bool> set of molecule-valued field names */
    private array $moleculeFields;
    private ?MoleculeResolver $moleculeResolver;
    /** @var array<string, array{unit:string, family:string}> expected unit per field */
    private array $expectedUnits;

    /**
     * @param float                  $numericTolerance relative tolerance for numeric fields
     * @param string[]               $moleculeFields   columns to compare by molecule identity
     * @param MoleculeResolver|null  $resolver         resolver for molecule fields (null = string compare)
     * @param array<string, array{unit:string, family:string}> $expectedUnits expected unit per numeric field
     */
    public function __construct(
        float $numericTolerance = 0.1,
        array $moleculeFields = [],
        ?MoleculeResolver $resolver = null,
        array $expectedUnits = []
    ) {
        $this->numericTolerance = $numericTolerance;
        $this->moleculeFields = array_fill_keys($moleculeFields, true);
        $this->moleculeResolver = $resolver;
        $this->expectedUnits = $expectedUnits;
    }

    /**
     * @param array<int, array<string,string>> $goldRows
     * @param array<int, array<string,string>> $extractedRows
     * @return array{precision:float, recall:float, f1:float, truePositives:int, goldCells:int,
     *               extractedCells:int, goldRows:int, extractedRows:int, matchedRows:int,
     *               perField:array<string,array{gold:int,correct:int}>, examples:string[]}
     */
    public function scorePublication(array $goldRows, array $extractedRows): array
    {
        $usedExtracted = [];
        $truePositives = 0;
        $perField = [];
        $examples = [];
        $matchedRows = 0;
        $unitChecked = 0;
        $unitConsistent = 0;

        foreach ($goldRows as $goldRow) {
            [$bestIdx, $bestScore] = $this->findBestMatch($goldRow, $extractedRows, $usedExtracted);
            $extractedRow = ($bestIdx !== null) ? $extractedRows[$bestIdx] : [];
            if ($bestIdx !== null && $bestScore > 0) {
                $usedExtracted[$bestIdx] = true;
                $matchedRows++;
            }

            foreach ($goldRow as $field => $goldValue) {
                if ($this->isEmpty($goldValue)) {
                    continue;
                }
                $perField[$field] = $perField[$field] ?? ['gold' => 0, 'correct' => 0];
                $perField[$field]['gold']++;

                $extractedValue = $extractedRow[$field] ?? '';

                // unit-correctness: does the extracted value carry a dimensionally consistent unit?
                if (isset($this->expectedUnits[$field]) && !$this->isEmpty($extractedValue)) {
                    $unitChecked++;
                    $extractedUnit = UnitConverter::parse($extractedValue)['unit'];
                    if (UnitConverter::inFamily($extractedUnit, $this->expectedUnits[$field]['family'])) {
                        $unitConsistent++;
                    }
                }

                if (!$this->isEmpty($extractedValue) && $this->valuesMatch($goldValue, $extractedValue, $field)) {
                    $truePositives++;
                    $perField[$field]['correct']++;
                } elseif (count($examples) < 25) {
                    $shown = $this->isEmpty($extractedValue) ? '(missing)' : $extractedValue;
                    $examples[] = "$field: expected '$goldValue', got '$shown'";
                }
            }
        }

        $goldCells = $this->countNonEmptyCells($goldRows);
        $extractedCells = $this->countNonEmptyCells($extractedRows);

        $recall = $goldCells > 0 ? $truePositives / $goldCells : 0.0;
        $precision = $extractedCells > 0 ? $truePositives / $extractedCells : 0.0;
        $f1 = ($precision + $recall) > 0 ? 2 * $precision * $recall / ($precision + $recall) : 0.0;

        return [
            'precision' => $precision,
            'recall' => $recall,
            'f1' => $f1,
            'truePositives' => $truePositives,
            'goldCells' => $goldCells,
            'extractedCells' => $extractedCells,
            'goldRows' => count($goldRows),
            'extractedRows' => count($extractedRows),
            'matchedRows' => $matchedRows,
            'unitChecked' => $unitChecked,
            'unitConsistent' => $unitConsistent,
            'perField' => $perField,
            'examples' => $examples,
        ];
    }

    /**
     * Aggregates per-publication scores into a corpus-level result (micro-averaged F1).
     *
     * @param array<int, array> $publicationScores results of scorePublication()
     * @return array{precision:float, recall:float, f1:float, perField:array<string,array{gold:int,correct:int,recall:float}>, examples:string[]}
     */
    public function aggregate(array $publicationScores): array
    {
        $tp = 0;
        $goldCells = 0;
        $extractedCells = 0;
        $unitChecked = 0;
        $unitConsistent = 0;
        $perField = [];
        $examples = [];

        foreach ($publicationScores as $score) {
            $tp += $score['truePositives'];
            $goldCells += $score['goldCells'];
            $extractedCells += $score['extractedCells'];
            $unitChecked += $score['unitChecked'] ?? 0;
            $unitConsistent += $score['unitConsistent'] ?? 0;
            foreach ($score['perField'] as $field => $counts) {
                $perField[$field] = $perField[$field] ?? ['gold' => 0, 'correct' => 0];
                $perField[$field]['gold'] += $counts['gold'];
                $perField[$field]['correct'] += $counts['correct'];
            }
            foreach ($score['examples'] as $ex) {
                if (count($examples) < 40) {
                    $examples[] = $ex;
                }
            }
        }

        foreach ($perField as $field => $counts) {
            $perField[$field]['recall'] = $counts['gold'] > 0 ? $counts['correct'] / $counts['gold'] : 0.0;
        }
        // worst fields first — most useful for the optimizer
        uasort($perField, fn($a, $b) => $a['recall'] <=> $b['recall']);

        $recall = $goldCells > 0 ? $tp / $goldCells : 0.0;
        $precision = $extractedCells > 0 ? $tp / $extractedCells : 0.0;
        $f1 = ($precision + $recall) > 0 ? 2 * $precision * $recall / ($precision + $recall) : 0.0;

        return [
            'precision' => $precision,
            'recall' => $recall,
            'f1' => $f1,
            'unitCorrectness' => $unitChecked > 0 ? $unitConsistent / $unitChecked : null,
            'unitChecked' => $unitChecked,
            'perField' => $perField,
            'examples' => $examples,
        ];
    }

    /**
     * @return array{0: int|null, 1: float} best extracted row index and its match score
     */
    private function findBestMatch(array $goldRow, array $extractedRows, array $usedExtracted): array
    {
        $bestIdx = null;
        $bestScore = -1.0;
        foreach ($extractedRows as $idx => $extractedRow) {
            if (isset($usedExtracted[$idx])) {
                continue;
            }
            $score = $this->rowMatchScore($goldRow, $extractedRow);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIdx = $idx;
            }
        }
        return [$bestIdx, max(0.0, $bestScore)];
    }

    private function rowMatchScore(array $goldRow, array $extractedRow): float
    {
        $matches = 0;
        $considered = 0;
        foreach ($goldRow as $field => $goldValue) {
            if ($this->isEmpty($goldValue)) {
                continue;
            }
            $considered++;
            $extractedValue = $extractedRow[$field] ?? '';
            if (!$this->isEmpty($extractedValue) && $this->valuesMatch($goldValue, $extractedValue, $field)) {
                $matches++;
            }
        }
        return $considered > 0 ? $matches / $considered : 0.0;
    }

    private function valuesMatch(string $gold, string $extracted, string $field = ''): bool
    {
        // Molecule-valued fields: compare by molecule identity when a resolver is available.
        if ($field !== '' && $this->moleculeResolver !== null && isset($this->moleculeFields[$field])) {
            return $this->moleculeResolver->canonicalize($gold) === $this->moleculeResolver->canonicalize($extracted);
        }

        // Unit-aware numeric comparison: normalize both values to the field's expected unit so
        // that e.g. "1 µM" and "1e-6 M" compare equal.
        if ($field !== '' && isset($this->expectedUnits[$field])) {
            $expected = $this->expectedUnits[$field];
            $goldParsed = UnitConverter::parse($gold);
            $extractedParsed = UnitConverter::parse($extracted);
            if ($goldParsed['number'] !== null && $extractedParsed['number'] !== null) {
                $goldCanon = UnitConverter::convertWithinFamily($goldParsed['number'], $goldParsed['unit'], $expected['unit'], $expected['family']);
                $extractedCanon = UnitConverter::convertWithinFamily($extractedParsed['number'], $extractedParsed['unit'], $expected['unit'], $expected['family']);
                if ($goldCanon !== null && $extractedCanon !== null) {
                    return $this->numbersClose($goldCanon, $extractedCanon);
                }
            }
        }

        $goldNum = $this->parseNumber($gold);
        $extractedNum = $this->parseNumber($extracted);
        if ($goldNum !== null && $extractedNum !== null) {
            return $this->numbersClose($goldNum, $extractedNum);
        }
        return $this->normalize($gold) === $this->normalize($extracted);
    }

    private function numbersClose(float $a, float $b): bool
    {
        $scale = max(abs($a), abs($b), 1e-12);
        return abs($a - $b) <= $this->numericTolerance * $scale;
    }

    private function parseNumber(string $value): ?float
    {
        $value = str_replace(['×10^', 'x10^', '·10^'], 'e', $value);
        if (preg_match('/-?\d+(?:[.,]\d+)?(?:[eE][-+]?\d+)?/', $value, $m)) {
            return (float) str_replace(',', '.', $m[0]);
        }
        return null;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value, " \t\n\r\0\x0B.;,");
    }

    private function isEmpty(string $value): bool
    {
        $v = trim($value);
        return $v === '' || strtolower($v) === 'n/a' || $v === '-';
    }

    private function countNonEmptyCells(array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            foreach ($row as $value) {
                if (!$this->isEmpty((string) $value)) {
                    $count++;
                }
            }
        }
        return $count;
    }
}
