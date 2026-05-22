<?php

namespace DIQA\ChemExtension\Eval;

/**
 * Minimal, dependency-free SVG bar chart — used for the cross-topic comparison (best F1 per
 * topic) in the evaluation report. Vector output, so it drops straight into the paper.
 */
class SvgBarChart
{
    private const COLOR = '#1f77b4';

    /**
     * @param array<int, array{label:string, value:float}> $bars
     * @param array{title?:string, ylabel?:string, width?:int, height?:int, yMax?:float} $opts
     */
    public static function render(array $bars, array $opts = []): string
    {
        $w = $opts['width'] ?? 720;
        $h = $opts['height'] ?? 420;
        $padL = 64; $padR = 24; $padT = 48; $padB = 90;
        $plotW = $w - $padL - $padR;
        $plotH = $h - $padT - $padB;

        $maxVal = $opts['yMax'] ?? 0.0;
        if ($maxVal <= 0.0) {
            foreach ($bars as $b) {
                $maxVal = max($maxVal, $b['value']);
            }
            $maxVal = $maxVal > 0 ? $maxVal : 1.0;
        }

        $sy = fn($v) => $padT + $plotH - ($v / $maxVal) * $plotH;

        $svg = [];
        $svg[] = sprintf('<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" font-family="sans-serif" font-size="12">', $w, $h, $w, $h);
        $svg[] = sprintf('<rect x="0" y="0" width="%d" height="%d" fill="white"/>', $w, $h);
        if (!empty($opts['title'])) {
            $svg[] = sprintf('<text x="%d" y="24" font-size="16" font-weight="bold" text-anchor="middle">%s</text>', (int) ($padL + $plotW / 2), self::esc($opts['title']));
        }

        // y gridlines + labels
        for ($i = 0; $i <= 5; $i++) {
            $v = $maxVal * $i / 5;
            $py = $sy($v);
            $svg[] = sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#eee"/>', $padL, $py, $padL + $plotW, $py);
            $svg[] = sprintf('<text x="%.1f" y="%.1f" text-anchor="end" fill="#555">%.2f</text>', $padL - 8, $py + 4, $v);
        }
        if (!empty($opts['ylabel'])) {
            $svg[] = sprintf('<text x="16" y="%.1f" text-anchor="middle" fill="#333" transform="rotate(-90 16 %.1f)">%s</text>', $padT + $plotH / 2, $padT + $plotH / 2, self::esc($opts['ylabel']));
        }

        $n = max(1, count($bars));
        $slot = $plotW / $n;
        $barW = $slot * 0.6;
        $i = 0;
        foreach ($bars as $bar) {
            $x = $padL + $i * $slot + ($slot - $barW) / 2;
            $y = $sy($bar['value']);
            $barH = $padT + $plotH - $y;
            $svg[] = sprintf('<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" fill="%s"/>', $x, $y, $barW, $barH, self::COLOR);
            $svg[] = sprintf('<text x="%.1f" y="%.1f" text-anchor="middle" fill="#333">%.3f</text>', $x + $barW / 2, $y - 6, $bar['value']);
            // x label, rotated for long topic names
            $cx = $x + $barW / 2;
            $ly = $padT + $plotH + 16;
            $svg[] = sprintf('<text x="%.1f" y="%.1f" text-anchor="end" fill="#333" transform="rotate(-30 %.1f %.1f)">%s</text>', $cx, $ly, $cx, $ly, self::esc($bar['label']));
            $i++;
        }

        // x axis
        $svg[] = sprintf('<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" stroke="#333"/>', $padL, $padT + $plotH, $padL + $plotW, $padT + $plotH);
        $svg[] = '</svg>';
        return implode("\n", $svg);
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
