<?php

namespace DIQA\ChemExtension\Eval;

/**
 * Minimal, dependency-free SVG line chart for the evaluation report. SVG is vector, so the output
 * goes straight into a paper (LaTeX `\includegraphics` of an SVG/PDF, or via Inkscape export).
 *
 * Renders one or more series over a shared x-axis (iterations), with axes, gridlines and a legend.
 */
class SvgLineChart
{
    private const COLORS = ['#1f77b4', '#d62728', '#2ca02c', '#9467bd', '#ff7f0e', '#8c564b', '#17becf'];

    /**
     * @param array<int, array{label:string, points: array<int, array{0: int|float, 1: float}>}> $series
     * @param array{title?:string, xlabel?:string, ylabel?:string, width?:int, height?:int, yMin?:float, yMax?:float} $opts
     */
    public static function render(array $series, array $opts = []): string
    {
        $w = $opts['width'] ?? 720;
        $h = $opts['height'] ?? 420;
        $padL = 64; $padR = 160; $padT = 48; $padB = 56;
        $plotW = $w - $padL - $padR;
        $plotH = $h - $padT - $padB;

        [$xMin, $xMax, $yMin, $yMax] = self::bounds($series, $opts);
        $xSpan = max(1e-9, $xMax - $xMin);
        $ySpan = max(1e-9, $yMax - $yMin);

        $sx = fn($x) => $padL + ($x - $xMin) / $xSpan * $plotW;
        $sy = fn($y) => $padT + $plotH - ($y - $yMin) / $ySpan * $plotH;

        $svg = [];
        $svg[] = sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" font-family="sans-serif" font-size="12">', $w, $h, $w, $h);
        $svg[] = sprintf('<rect x="0" y="0" width="%d" height="%d" fill="white"/>', $w, $h);

        if (!empty($opts['title'])) {
            $svg[] = sprintf('<text x="%d" y="24" font-size="16" font-weight="bold" text-anchor="middle">%s</text>', (int) ($padL + $plotW / 2), self::esc($opts['title']));
        }

        // y gridlines + labels (5 ticks)
        for ($i = 0; $i <= 5; $i++) {
            $yVal = $yMin + $ySpan * $i / 5;
            $py = $sy($yVal);
            $svg[] = sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#eee"/>', $padL, $py, $padL + $plotW, $py);
            $svg[] = sprintf('<text x="%.1f" y="%.1f" text-anchor="end" fill="#555">%.2f</text>', $padL - 8, $py + 4, $yVal);
        }
        // x ticks (integer iterations)
        for ($x = (int) ceil($xMin); $x <= (int) floor($xMax); $x++) {
            $px = $sx($x);
            $svg[] = sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#eee"/>', $px, $padT, $px, $padT + $plotH);
            $svg[] = sprintf('<text x="%.1f" y="%.1f" text-anchor="middle" fill="#555">%d</text>', $px, $padT + $plotH + 20, $x);
        }

        // axes
        $svg[] = sprintf('<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" stroke="#333"/>', $padL, $padT + $plotH, $padL + $plotW, $padT + $plotH);
        $svg[] = sprintf('<line x1="%d" y1="%d" x2="%d" y2="%.1f" stroke="#333"/>', $padL, $padT, $padL, $padT + $plotH);
        if (!empty($opts['xlabel'])) {
            $svg[] = sprintf('<text x="%.1f" y="%d" text-anchor="middle" fill="#333">%s</text>', $padL + $plotW / 2, $h - 14, self::esc($opts['xlabel']));
        }
        if (!empty($opts['ylabel'])) {
            $svg[] = sprintf('<text x="16" y="%.1f" text-anchor="middle" fill="#333" transform="rotate(-90 16 %.1f)">%s</text>', $padT + $plotH / 2, $padT + $plotH / 2, self::esc($opts['ylabel']));
        }

        // series
        $li = 0;
        foreach ($series as $idx => $s) {
            $color = self::COLORS[$idx % count(self::COLORS)];
            $pts = [];
            foreach ($s['points'] as $p) {
                $pts[] = sprintf('%.1f,%.1f', $sx($p[0]), $sy($p[1]));
            }
            if (!empty($pts)) {
                $svg[] = sprintf('<polyline fill="none" stroke="%s" stroke-width="2" points="%s"/>', $color, implode(' ', $pts));
                foreach ($s['points'] as $p) {
                    $svg[] = sprintf('<circle cx="%.1f" cy="%.1f" r="2.5" fill="%s"/>', $sx($p[0]), $sy($p[1]), $color);
                }
            }
            // legend
            $ly = $padT + 6 + $li * 20;
            $svg[] = sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" stroke="%s" stroke-width="2"/>', $padL + $plotW + 16, $ly, $padL + $plotW + 40, $ly, $color);
            $svg[] = sprintf('<text x="%d" y="%d" fill="#333">%s</text>', $padL + $plotW + 46, $ly + 4, self::esc($s['label']));
            $li++;
        }

        $svg[] = '</svg>';
        return implode("\n", $svg);
    }

    private static function bounds(array $series, array $opts): array
    {
        $xs = [];
        $ys = [];
        foreach ($series as $s) {
            foreach ($s['points'] as $p) {
                $xs[] = $p[0];
                $ys[] = $p[1];
            }
        }
        $xMin = empty($xs) ? 0 : min($xs);
        $xMax = empty($xs) ? 1 : max($xs);
        $yMin = $opts['yMin'] ?? (empty($ys) ? 0.0 : min($ys));
        $yMax = $opts['yMax'] ?? (empty($ys) ? 1.0 : max($ys));
        if ($yMin === $yMax) {
            $yMin -= 0.5;
            $yMax += 0.5;
        }
        return [$xMin, $xMax, $yMin, $yMax];
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
