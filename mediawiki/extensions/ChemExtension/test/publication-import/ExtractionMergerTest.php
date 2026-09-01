<?php

namespace DIQA\ChemExtension\PublicationImport;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the deterministic N-pass merger. These are pure-logic tests — no API calls,
 * no OpenAI dependency. They cover the merger contract that EnsembleExtractor relies on
 * to turn N stochastic extractions into ONE canonical wikitext.
 */
class ExtractionMergerTest extends TestCase
{
    /** Small helper: build a fenced csv block wikitext with a fixed prose skeleton. */
    private static function wrapWithProse(string $csv): string
    {
        return "== Abstract Summary ==\nTest summary text.\n\n"
             . "== Investigation ==\n"
             . $csv;
    }

    public function testUnionRowsFromMultiplePasses(): void
    {
        // Pass 1 sees experiments A + B; Pass 2 sees A + C; Pass 3 sees B + C.
        // Union: A, B, C should all appear in the merged output.
        $pass1 = self::wrapWithProse("```csv\ncatalyst,cat conc,PS,PS conc\nFe-1,50,Ir(ppy)3,0.5\nFe-2,50,Ir(ppy)3,0.5\n```");
        $pass2 = self::wrapWithProse("```csv\ncatalyst,cat conc,PS,PS conc\nFe-1,50,Ir(ppy)3,0.5\nFe-3,50,Ir(ppy)3,0.5\n```");
        $pass3 = self::wrapWithProse("```csv\ncatalyst,cat conc,PS,PS conc\nFe-2,50,Ir(ppy)3,0.5\nFe-3,50,Ir(ppy)3,0.5\n```");
        $merged = ExtractionMerger::merge([$pass1, $pass2, $pass3]);
        // All three catalysts must appear in the merged CSV
        $this->assertStringContainsString('Fe-1', $merged);
        $this->assertStringContainsString('Fe-2', $merged);
        $this->assertStringContainsString('Fe-3', $merged);
        // Count Fe-{n} occurrences in the merged CSV block only
        preg_match('/```csv(.*?)```/s', $merged, $csvMatch);
        $csvBlock = $csvMatch[1] ?? '';
        $this->assertSame(1, substr_count($csvBlock, 'Fe-1'), 'Fe-1 must appear exactly once (deduped)');
        $this->assertSame(1, substr_count($csvBlock, 'Fe-2'), 'Fe-2 must appear exactly once');
        $this->assertSame(1, substr_count($csvBlock, 'Fe-3'), 'Fe-3 must appear exactly once');
    }

    public function testCellMajorityWithClearMajority(): void
    {
        // 3 passes agree on '50' for a cell → majority wins clearly.
        $this->assertSame('50', ExtractionMerger::cellMajority(['50', '50', '50']));
        $this->assertSame('50', ExtractionMerger::cellMajority(['50', '50', '25']));
    }

    public function testCellMajorityWithTie(): void
    {
        // 3-way tie (each value appears exactly once) → tiebreaker: longest first
        $this->assertSame('50 mW cm^-2', ExtractionMerger::cellMajority(['50', '50 mW', '50 mW cm^-2']));
        // Same-length tie → alphabetical first (deterministic)
        $this->assertSame('aaa', ExtractionMerger::cellMajority(['bbb', 'aaa', 'ccc']));
    }

    public function testCellMajorityIgnoresEmptyValues(): void
    {
        // Only 1 non-empty value → it wins even though 2/3 are empty
        $this->assertSame('50', ExtractionMerger::cellMajority(['', '50', '']));
        // All empty → empty result
        $this->assertSame('', ExtractionMerger::cellMajority(['', '', '']));
    }

    public function testFingerprintNormalization(): void
    {
        // Variations of case + whitespace + trailing/leading spaces should collapse to
        // the same fingerprint so those rows group as one experiment.
        $row1 = ['catalyst' => 'Fe-1', 'PS' => 'Ir(ppy)3', 'cat conc' => '50', 'PS conc' => '0.5'];
        $row2 = ['catalyst' => ' fe-1 ', 'PS' => 'IR(PPY)3', 'cat conc' => '50 ', 'PS conc' => '0.5'];
        $row3 = ['catalyst' => 'FE-1', 'PS' => "Ir(ppy)3\t", 'cat conc' => '50', 'PS conc' => '0.5'];
        $fp1 = ExtractionMerger::rowFingerprint($row1);
        $fp2 = ExtractionMerger::rowFingerprint($row2);
        $fp3 = ExtractionMerger::rowFingerprint($row3);
        $this->assertSame($fp1, $fp2, 'case + whitespace variants must fingerprint identically');
        $this->assertSame($fp1, $fp3, 'more variants must fingerprint identically');
        // Different catalyst → different fingerprint
        $row4 = ['catalyst' => 'Fe-2', 'PS' => 'Ir(ppy)3', 'cat conc' => '50', 'PS conc' => '0.5'];
        $this->assertNotSame($fp1, ExtractionMerger::rowFingerprint($row4));
    }

    public function testProseSectionMergePicksMostCompletePass(): void
    {
        // Pass A has 2 sections (Abstract + Investigation), Pass B has 4 sections.
        // Merger should use Pass B's prose skeleton for the output.
        $passA = "== Abstract Summary ==\nShort.\n\n== Investigation ==\n"
               . "```csv\ncatalyst,PS\nFe-1,Ir\n```";
        $passB = "== Abstract Summary ==\nLong.\n\n"
               . "== Advances and Special Progress ==\nMore.\n\n"
               . "== Catalyst ==\nDetails.\n\n"
               . "== Investigation ==\n"
               . "```csv\ncatalyst,PS\nFe-1,Ir\n```";
        $merged = ExtractionMerger::merge([$passA, $passB]);
        // The merger should have preserved Pass B's Advances + Catalyst sections
        $this->assertStringContainsString('== Advances and Special Progress ==', $merged);
        $this->assertStringContainsString('== Catalyst ==', $merged);
        $this->assertStringContainsString('More.', $merged);
    }

    public function testLegacyModeSinglePassIsIdentityFunction(): void
    {
        // With only ONE pass, the merger must return the input verbatim — this is the
        // legacy path (N=1) that preserves pre-ensemble behaviour.
        $single = self::wrapWithProse("```csv\ncatalyst,PS\nFe-1,Ir(ppy)3\n```");
        $this->assertSame($single, ExtractionMerger::merge([$single]));
    }

    public function testMergeEmptyPassesReturnsEmptyString(): void
    {
        // Defensive: all N passes failed / are empty → return '' rather than crashing.
        $this->assertSame('', ExtractionMerger::merge([]));
        $this->assertSame('', ExtractionMerger::merge(['', '', '']));
    }

    public function testCsvBlockRoundtripPreservesColumnsAndCells(): void
    {
        // 3 passes with identical rows → merged output must contain those rows once each
        // with the correct cell values.
        $csv = "```csv\ncatalyst,cat conc,PS,PS conc,irr time\nFe-1,50,Ir(ppy)3,0.5,15\nFe-2,50,Ir(ppy)3,0.5,15\n```";
        $wiki = self::wrapWithProse($csv);
        $merged = ExtractionMerger::merge([$wiki, $wiki, $wiki]);
        preg_match('/```csv(.*?)```/s', $merged, $out);
        $body = $out[1] ?? '';
        // Header preserved
        $this->assertMatchesRegularExpression('/catalyst.*cat conc.*PS.*PS conc.*irr time/', $body);
        // Each row present exactly once
        $this->assertSame(1, substr_count($body, 'Fe-1'));
        $this->assertSame(1, substr_count($body, 'Fe-2'));
        // Cell values preserved
        $this->assertStringContainsString('Ir(ppy)3', $body);
        $this->assertStringContainsString('15', $body);
    }

    public function testDeterministicOrderingIndependentOfInputPassOrder(): void
    {
        // Merging the same 3 passes in different orders must yield the same CSV block
        // (row ordering deterministic by fingerprint).
        $p1 = self::wrapWithProse("```csv\ncatalyst,PS\nAlpha,X\n```");
        $p2 = self::wrapWithProse("```csv\ncatalyst,PS\nBeta,Y\n```");
        $p3 = self::wrapWithProse("```csv\ncatalyst,PS\nGamma,Z\n```");
        $mergedABC = ExtractionMerger::merge([$p1, $p2, $p3]);
        $mergedCBA = ExtractionMerger::merge([$p3, $p2, $p1]);
        preg_match('/```csv(.*?)```/s', $mergedABC, $m1);
        preg_match('/```csv(.*?)```/s', $mergedCBA, $m2);
        // The CSV blocks must be identical regardless of input order.
        $this->assertSame(trim($m1[1] ?? ''), trim($m2[1] ?? ''));
    }
}
