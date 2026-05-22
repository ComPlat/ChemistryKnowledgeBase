<?php

namespace DIQA\ChemExtension\Eval;

/**
 * Turns the per-iteration run records written by {@see EvalMemory} into paper-ready artefacts:
 *  - metrics.csv     tidy data (one row per iteration) for any plotting tool,
 *  - convergence.svg vector line chart of the quality metrics over iterations (shows trend /
 *                    convergence at a glance),
 *  - tokens.svg      efficiency trend (tokens per publication),
 *  - metrics.tex     a pgfplots snippet + a booktabs summary table to paste into the paper,
 *  - report.md       a short textual summary (best iteration, start→best delta, convergence).
 *
 * Output goes to eval/<topic>/report/.
 */
class MetricsReport
{
    /** metric key => human label; the 0..1 metrics are drawn together in convergence.svg */
    private const QUALITY_METRICS = [
        'f1' => 'F1',
        'precision' => 'Precision',
        'recall' => 'Recall',
        'unitCorrectness' => 'Unit correctness',
        'sanityPassRate' => 'Sanity pass rate',
        'avgConfidence' => 'Critic confidence',
        'proseSimilarity' => 'Prose similarity',
    ];

    private string $baseDir;

    public function __construct(?string $evalBaseDir = null)
    {
        $this->baseDir = $evalBaseDir ?? dirname(__DIR__, 2) . '/eval';
    }

    /**
     * @return string[] paths of the generated files
     */
    public function generateForTopic(string $topic): array
    {
        $rows = $this->loadRuns($topic);
        if (empty($rows)) {
            throw new \RuntimeException("No runs found for topic '$topic' (run the loop first).");
        }
        $reportDir = $this->baseDir . '/' . $topic . '/report';
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0775, true);
        }

        $written = [];
        $written[] = $this->writeCsv($reportDir, $rows);
        $written[] = $this->writeConvergenceSvg($reportDir, $rows, $topic);
        $written[] = $this->writeTokensSvg($reportDir, $rows, $topic);
        $written[] = $this->writeTex($reportDir, $rows, $topic);
        $written[] = $this->writeMarkdown($reportDir, $rows, $topic);
        return array_values(array_filter($written));
    }

    /**
     * Best (highest-F1) iteration metrics for a topic, or null if it has no runs.
     *
     * @return array<string, float|int|null>|null
     */
    public function bestMetrics(string $topic): ?array
    {
        $rows = $this->loadRuns($topic);
        return empty($rows) ? null : $this->bestRow($rows);
    }

    /**
     * Cross-topic comparison: best F1 per topic as a bar chart + CSV + a short Markdown table.
     * Writes to eval/report/. Useful as a single "results" figure for the paper.
     *
     * @param string[] $topics
     * @return string[] paths of generated files (empty if fewer than one topic has runs)
     */
    public function generateComparison(array $topics): array
    {
        $bars = [];
        $csv = ['topic,best_f1,best_precision,best_recall,best_iteration'];
        $mdRows = [];
        foreach ($topics as $topic) {
            $best = $this->bestMetrics($topic);
            if ($best === null) {
                continue;
            }
            $display = str_replace('_', ' ', $topic);
            $bars[] = ['label' => $display, 'value' => (float) ($best['f1'] ?? 0)];
            $csv[] = sprintf('%s,%s,%s,%s,%d', $topic, $best['f1'] ?? '', $best['precision'] ?? '', $best['recall'] ?? '', $best['iteration'] ?? 0);
            $mdRows[] = sprintf('| %s | %s | %s | %s | %d |', $display, $this->fmt($best['f1']), $this->fmt($best['precision']), $this->fmt($best['recall']), $best['iteration'] ?? 0);
        }
        if (empty($bars)) {
            return [];
        }

        $dir = $this->baseDir . '/report';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $written = [];
        $svg = SvgBarChart::render($bars, ['title' => 'Best F1 per topic', 'ylabel' => 'F1', 'yMax' => 1.0]);
        file_put_contents($dir . '/topic_comparison.svg', $svg);
        $written[] = $dir . '/topic_comparison.svg';

        file_put_contents($dir . '/topic_comparison.csv', implode("\n", $csv) . "\n");
        $written[] = $dir . '/topic_comparison.csv';

        $md = "# Cross-topic comparison (best F1 per topic)\n\n"
            . "| Topic | F1 | Precision | Recall | Best it. |\n|---|----|-----------|--------|----------|\n"
            . implode("\n", $mdRows) . "\n\nFigure: `topic_comparison.svg`.\n";
        file_put_contents($dir . '/topic_comparison.md', $md);
        $written[] = $dir . '/topic_comparison.md';

        return $written;
    }

    /** @return array<int, array<string, float|int|null>> one row per iteration, sorted */
    private function loadRuns(string $topic): array
    {
        $runsDir = $this->baseDir . '/' . $topic . '/runs';
        $rows = [];
        foreach (glob($runsDir . '/*.json') ?: [] as $file) {
            $run = json_decode(file_get_contents($file), true);
            if (!is_array($run) || !isset($run['iteration'])) {
                continue;
            }
            $m = $run['metric'] ?? [];
            $row = ['iteration' => (int) $run['iteration']];
            foreach (array_keys(self::QUALITY_METRICS) as $key) {
                $row[$key] = isset($m[$key]) && $m[$key] !== null ? (float) $m[$key] : null;
            }
            $row['f1PerKToken'] = isset($m['f1PerKToken']) ? (float) $m['f1PerKToken'] : null;
            $row['tokensPerPub'] = $m['tokens']['perPublication'] ?? null;
            $rows[$row['iteration']] = $row;
        }
        ksort($rows);
        return array_values($rows);
    }

    private function writeCsv(string $dir, array $rows): string
    {
        $cols = array_merge(['iteration'], array_keys(self::QUALITY_METRICS), ['f1PerKToken', 'tokensPerPub']);
        $lines = [implode(',', $cols)];
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(fn($c) => $row[$c] ?? '', $cols));
        }
        $path = $dir . '/metrics.csv';
        file_put_contents($path, implode("\n", $lines) . "\n");
        return $path;
    }

    private function writeConvergenceSvg(string $dir, array $rows, string $topic): string
    {
        $series = [];
        foreach (self::QUALITY_METRICS as $key => $label) {
            $points = [];
            foreach ($rows as $row) {
                if ($row[$key] !== null) {
                    $points[] = [$row['iteration'], $row[$key]];
                }
            }
            if (!empty($points)) {
                $series[] = ['label' => $label, 'points' => $points];
            }
        }
        $svg = SvgLineChart::render($series, [
            'title' => "Metric convergence — " . str_replace('_', ' ', $topic),
            'xlabel' => 'Iteration',
            'ylabel' => 'Score',
            'yMin' => 0.0,
            'yMax' => 1.0,
        ]);
        $path = $dir . '/convergence.svg';
        file_put_contents($path, $svg);
        return $path;
    }

    private function writeTokensSvg(string $dir, array $rows, string $topic): string
    {
        $points = [];
        foreach ($rows as $row) {
            if ($row['tokensPerPub'] !== null) {
                $points[] = [$row['iteration'], (float) $row['tokensPerPub']];
            }
        }
        if (empty($points)) {
            return '';
        }
        $svg = SvgLineChart::render(
            [['label' => 'Tokens/publication', 'points' => $points]],
            ['title' => "Efficiency — " . str_replace('_', ' ', $topic), 'xlabel' => 'Iteration', 'ylabel' => 'Tokens']
        );
        $path = $dir . '/tokens.svg';
        file_put_contents($path, $svg);
        return $path;
    }

    private function writeTex(string $dir, array $rows, string $topic): string
    {
        // pgfplots coordinates for F1/precision/recall + a booktabs summary row
        $coords = function (string $key) use ($rows): string {
            $c = [];
            foreach ($rows as $row) {
                if ($row[$key] !== null) {
                    $c[] = sprintf('(%d,%.4f)', $row['iteration'], $row[$key]);
                }
            }
            return implode(' ', $c);
        };

        $best = $this->bestRow($rows);
        $first = $rows[0];
        $tex = "% Convergence plot for topic: $topic\n"
            . "\\begin{tikzpicture}\n\\begin{axis}[xlabel={Iteration},ylabel={Score},ymin=0,ymax=1,legend pos=south east,width=\\linewidth,height=6cm]\n"
            . "\\addplot coordinates {" . $coords('f1') . "}; \\addlegendentry{F1}\n"
            . "\\addplot coordinates {" . $coords('precision') . "}; \\addlegendentry{Precision}\n"
            . "\\addplot coordinates {" . $coords('recall') . "}; \\addlegendentry{Recall}\n"
            . "\\end{axis}\n\\end{tikzpicture}\n\n"
            . "% Summary (first vs best iteration)\n"
            . "\\begin{tabular}{lcc}\n\\hline\nMetric & Iteration 1 & Best (it.\\ {$best['iteration']}) \\\\\n\\hline\n"
            . sprintf("F1 & %.3f & %.3f \\\\\n", $first['f1'] ?? 0, $best['f1'] ?? 0)
            . sprintf("Precision & %.3f & %.3f \\\\\n", $first['precision'] ?? 0, $best['precision'] ?? 0)
            . sprintf("Recall & %.3f & %.3f \\\\\n", $first['recall'] ?? 0, $best['recall'] ?? 0)
            . "\\hline\n\\end{tabular}\n";
        $path = $dir . '/metrics.tex';
        file_put_contents($path, $tex);
        return $path;
    }

    private function writeMarkdown(string $dir, array $rows, string $topic): string
    {
        $best = $this->bestRow($rows);
        $first = $rows[0];
        $last = $rows[count($rows) - 1];
        $delta = ($best['f1'] ?? 0) - ($first['f1'] ?? 0);
        $converged = abs(($last['f1'] ?? 0) - ($best['f1'] ?? 0)) < 0.01;

        $md = "# Evaluation report — " . str_replace('_', ' ', $topic) . "\n\n"
            . "- Iterations: " . count($rows) . "\n"
            . sprintf("- F1: start %.3f → best %.3f (iteration %d), Δ %+.3f\n", $first['f1'] ?? 0, $best['f1'] ?? 0, $best['iteration'], $delta)
            . sprintf("- Best precision/recall: %.3f / %.3f\n", $best['precision'] ?? 0, $best['recall'] ?? 0)
            . "- " . ($converged ? "Converged (last ≈ best)." : "Not yet converged — more iterations may still help.") . "\n\n"
            . "Figures: `convergence.svg`, `tokens.svg`. Data: `metrics.csv`. Paper snippet: `metrics.tex`.\n\n"
            . "| it | F1 | P | R | units | sanity | conf | prose | tok/pub |\n|---|----|---|---|-------|--------|------|-------|---------|\n";
        foreach ($rows as $row) {
            $md .= sprintf(
                "| %d | %s | %s | %s | %s | %s | %s | %s | %s |\n",
                $row['iteration'],
                $this->fmt($row['f1']), $this->fmt($row['precision']), $this->fmt($row['recall']),
                $this->fmt($row['unitCorrectness']), $this->fmt($row['sanityPassRate']),
                $this->fmt($row['avgConfidence']), $this->fmt($row['proseSimilarity']),
                $row['tokensPerPub'] ?? '—'
            );
        }
        $path = $dir . '/report.md';
        file_put_contents($path, $md);
        return $path;
    }

    private function bestRow(array $rows): array
    {
        $best = $rows[0];
        foreach ($rows as $row) {
            if (($row['f1'] ?? -1) > ($best['f1'] ?? -1)) {
                $best = $row;
            }
        }
        return $best;
    }

    private function fmt(?float $v): string
    {
        return $v === null ? '—' : number_format($v, 3);
    }
}
