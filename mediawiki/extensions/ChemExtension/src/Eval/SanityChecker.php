<?php

namespace DIQA\ChemExtension\Eval;

/**
 * Deterministic, model-independent plausibility checks on extracted experiment rows.
 *
 * These catch physically impossible values regardless of which model produced them — e.g. a
 * faradaic efficiency above 100%, a negative turnover number, a value outside [0,100], or a
 * non-positive binding constant. They run on the extraction alone (no gold needed), so they serve
 * both as an evaluation signal and as a guard before a page is written to the wiki.
 *
 * The rules are topic-agnostic: they are supplied by {@see TopicProfile::sanityRules()} (derived
 * generically per topic, refined by profile.json), so the checker itself stays generic and
 * unit-testable.
 */
class SanityChecker
{
    /** tolerance above an upper bound before flagging (e.g. rounding to 100.x %) */
    private float $tolerance;

    public function __construct(float $tolerance = 0.02)
    {
        $this->tolerance = $tolerance;
    }

    /**
     * @param array{nonNegative?:string[], positive?:string[], percentage?:string[], sumAtMost?:array} $rules
     * @param array<int, array<string,string>> $rows
     * @return array{checks:int, failed:int, violations:string[]}
     */
    public function check(array $rules, array $rows): array
    {
        $checks = 0;
        $failed = 0;
        $violations = [];

        foreach ($rows as $i => $row) {
            foreach (($rules['nonNegative'] ?? []) as $field) {
                $n = $this->num($row[$field] ?? '');
                if ($n === null) {
                    continue;
                }
                $checks++;
                if ($n < 0) {
                    $failed++;
                    $this->add($violations, "row $i: $field is negative ($n)");
                }
            }
            foreach (($rules['positive'] ?? []) as $field) {
                $n = $this->num($row[$field] ?? '');
                if ($n === null) {
                    continue;
                }
                $checks++;
                if ($n <= 0) {
                    $failed++;
                    $this->add($violations, "row $i: $field must be > 0 ($n)");
                }
            }
            foreach (($rules['percentage'] ?? []) as $field) {
                $n = $this->num($row[$field] ?? '');
                if ($n === null) {
                    continue;
                }
                $checks++;
                if ($n < 0 || $n > 100 * (1 + $this->tolerance)) {
                    $failed++;
                    $this->add($violations, "row $i: $field out of [0,100] ($n)");
                }
            }
            foreach (($rules['sumAtMost'] ?? []) as $rule) {
                $sum = 0.0;
                $present = false;
                foreach (($rule['fields'] ?? []) as $field) {
                    $n = $this->num($row[$field] ?? '');
                    if ($n !== null) {
                        $sum += $n;
                        $present = true;
                    }
                }
                if (!$present) {
                    continue;
                }
                $checks++;
                $max = (float) ($rule['max'] ?? 100.0);
                if ($sum > $max * (1 + $this->tolerance)) {
                    $failed++;
                    $this->add($violations, sprintf("row %d: %s sums to %.1f (> %.0f)", $i, $rule['label'] ?? 'group', $sum, $max));
                }
            }
        }

        return ['checks' => $checks, 'failed' => $failed, 'violations' => $violations];
    }

    private function num(string $value): ?float
    {
        return UnitConverter::parse($value)['number'];
    }

    private function add(array &$violations, string $message): void
    {
        if (count($violations) < 30) {
            $violations[] = $message;
        }
    }
}
