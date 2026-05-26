<?php

namespace DIQA\ChemExtension\Eval;

/**
 * Applies human-confirmed corrections to a gold entry's experiments. Kept pure (no IO) so it is
 * unit-testable; the maintenance script handles loading/saving the gold JSON files.
 *
 * A correction targets one cell: { "row": <int>, "field": <string>, "value": <string> }.
 */
class GoldCorrections
{
    /**
     * @param array $goldData      decoded gold JSON ({doi, topic, pdf, experiments})
     * @param array<int, array{row:int, field:string, value:mixed}> $corrections
     * @return array{data:array, applied:int, skipped:string[]} updated data + how many applied
     */
    public static function apply(array $goldData, array $corrections): array
    {
        $experiments = $goldData['experiments'] ?? [];
        $applied = 0;
        $skipped = [];
        foreach ($corrections as $c) {
            if (!isset($c['row'], $c['field'])) {
                $skipped[] = 'malformed correction (missing row/field)';
                continue;
            }
            $row = (int) $c['row'];
            $field = (string) $c['field'];
            if (!isset($experiments[$row])) {
                $skipped[] = "row $row does not exist";
                continue;
            }
            $experiments[$row][$field] = (string) ($c['value'] ?? '');
            $applied++;
        }
        $goldData['experiments'] = $experiments;
        return ['data' => $goldData, 'applied' => $applied, 'skipped' => $skipped];
    }
}
