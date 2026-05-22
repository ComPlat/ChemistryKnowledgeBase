<?php

namespace DIQA\ChemExtension\Eval;

use PHPUnit\Framework\TestCase;

class MetricsReportTest extends TestCase
{
    public function testSvgLineChartRendersSeries(): void
    {
        $svg = SvgLineChart::render(
            [['label' => 'F1', 'points' => [[1, 0.4], [2, 0.6], [3, 0.75]]]],
            ['title' => 'Test', 'xlabel' => 'Iteration', 'ylabel' => 'Score', 'yMin' => 0.0, 'yMax' => 1.0]
        );
        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('<polyline', $svg);
        $this->assertStringContainsString('Test', $svg);
        $this->assertStringContainsString('F1', $svg);
    }

    public function testGenerateForTopicWritesArtefacts(): void
    {
        $base = sys_get_temp_dir() . '/chemext_report_' . uniqid();
        $runs = $base . '/T/runs';
        mkdir($runs, 0777, true);

        file_put_contents($runs . '/1.json', json_encode([
            'iteration' => 1,
            'metric' => ['f1' => 0.40, 'precision' => 0.5, 'recall' => 0.33,
                'unitCorrectness' => 0.8, 'tokens' => ['perPublication' => 5000], 'f1PerKToken' => 0.08],
        ]));
        file_put_contents($runs . '/2.json', json_encode([
            'iteration' => 2,
            'metric' => ['f1' => 0.70, 'precision' => 0.75, 'recall' => 0.66,
                'unitCorrectness' => 0.9, 'tokens' => ['perPublication' => 4200], 'f1PerKToken' => 0.166],
        ]));

        $report = new MetricsReport($base);
        $files = $report->generateForTopic('T');

        $this->assertNotEmpty($files);
        $this->assertFileExists($base . '/T/report/metrics.csv');
        $this->assertFileExists($base . '/T/report/convergence.svg');
        $this->assertFileExists($base . '/T/report/report.md');

        $csv = file_get_contents($base . '/T/report/metrics.csv');
        $this->assertStringContainsString('iteration,f1,precision', $csv);
        $this->assertStringContainsString('0.7', $csv);

        $md = file_get_contents($base . '/T/report/report.md');
        $this->assertStringContainsString('best 0.700', $md);

        $this->rrmdir($base);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $e) {
            if ($e === '.' || $e === '..') {
                continue;
            }
            $p = "$dir/$e";
            is_dir($p) ? $this->rrmdir($p) : @unlink($p);
        }
        @rmdir($dir);
    }
}
