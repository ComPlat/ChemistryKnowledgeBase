<?php

namespace DIQA\ChemExtension\Eval;

use PHPUnit\Framework\TestCase;

class GoldAuditTest extends TestCase
{
    public function testParseFindingsKeepsOnlyConfidentRealChanges(): void
    {
        $gold = [[
            'catalyst' => 'Molecule:100',
            'Turnover_number__CO' => '700',
        ]];
        $json = json_encode(['findings' => [
            // valid: confident, real cell, actually changes
            ['index' => 0, 'field' => 'Turnover_number__CO', 'gold_value' => '700', 'correct_value' => '713', 'confidence' => 0.9, 'evidence' => 'TON 713'],
            // molecule reference -> never flagged
            ['index' => 0, 'field' => 'catalyst', 'gold_value' => 'Molecule:100', 'correct_value' => 'Molecule:200', 'confidence' => 0.95, 'evidence' => 'x'],
            // no actual change
            ['index' => 0, 'field' => 'Turnover_number__CO', 'gold_value' => '700', 'correct_value' => '700', 'confidence' => 0.9, 'evidence' => 'x'],
            // below threshold
            ['index' => 0, 'field' => 'Turnover_number__CO', 'gold_value' => '700', 'correct_value' => '999', 'confidence' => 0.4, 'evidence' => 'x'],
            // non-existent cell
            ['index' => 0, 'field' => 'nope', 'gold_value' => '', 'correct_value' => 'x', 'confidence' => 0.9, 'evidence' => 'x'],
        ]]);

        $findings = GoldAuditor::parseFindings($json, $gold, 0.7);

        $this->assertCount(1, $findings);
        $this->assertSame(0, $findings[0]['rowIndex']);
        $this->assertSame('Turnover_number__CO', $findings[0]['field']);
        $this->assertSame('713', $findings[0]['suggestedValue']);
    }

    public function testApplyCorrections(): void
    {
        $gold = ['doi' => '10.x/y', 'experiments' => [['a' => '1', 'b' => '2'], ['a' => '3']]];
        $corrections = [
            ['row' => 0, 'field' => 'b', 'value' => '9'],
            ['row' => 5, 'field' => 'a', 'value' => 'x'], // out of range
        ];
        $res = GoldCorrections::apply($gold, $corrections);

        $this->assertSame(1, $res['applied']);
        $this->assertSame('9', $res['data']['experiments'][0]['b']);
        $this->assertNotEmpty($res['skipped']);
    }
}
