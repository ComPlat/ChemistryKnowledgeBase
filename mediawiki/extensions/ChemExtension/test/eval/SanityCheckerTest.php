<?php

namespace DIQA\ChemExtension\Eval;

use PHPUnit\Framework\TestCase;

class SanityCheckerTest extends TestCase
{
    private function rules(): array
    {
        return [
            'nonNegative' => ['ton'],
            'positive' => ['ka'],
            'percentage' => ['fe__CO', 'fe__H2'],
            'sumAtMost' => [['label' => 'fe', 'max' => 100.0, 'fields' => ['fe__CO', 'fe__H2']]],
        ];
    }

    public function testCleanRowsHaveNoFailures(): void
    {
        $checker = new SanityChecker();
        $rows = [['ton' => '120', 'ka' => '1e9', 'fe__CO' => '40', 'fe__H2' => '50']];
        $res = $checker->check($this->rules(), $rows);
        $this->assertSame(0, $res['failed']);
        $this->assertGreaterThan(0, $res['checks']);
    }

    public function testDetectsViolations(): void
    {
        $checker = new SanityChecker();
        $rows = [
            ['ton' => '-3', 'ka' => '0', 'fe__CO' => '120', 'fe__H2' => '50'], // neg, non-positive, >100, and sum 170>100
        ];
        $res = $checker->check($this->rules(), $rows);
        // ton<0, ka<=0, fe__CO>100, sum>100  => 4 failures
        $this->assertSame(4, $res['failed']);
        $this->assertNotEmpty($res['violations']);
    }

    public function testIgnoresMissingFields(): void
    {
        $checker = new SanityChecker();
        $res = $checker->check($this->rules(), [['somethingElse' => 'x']]);
        $this->assertSame(0, $res['checks']);
    }
}
