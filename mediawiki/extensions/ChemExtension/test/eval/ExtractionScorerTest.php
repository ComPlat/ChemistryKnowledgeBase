<?php

namespace DIQA\ChemExtension\Eval;

use PHPUnit\Framework\TestCase;

class ExtractionScorerTest extends TestCase
{
    private function gold(): array
    {
        return [
            ['host' => 'CB[7]', 'guest' => 'adamantane', 'ka' => '1.0e9', 'guest_host_ratio' => '1:1'],
            ['host' => 'beta-CD', 'guest' => 'ferrocene', 'ka' => '3000', 'guest_host_ratio' => '1:1'],
        ];
    }

    public function testPerfectExtractionScoresOne(): void
    {
        $scorer = new ExtractionScorer();
        $score = $scorer->scorePublication($this->gold(), $this->gold());
        $this->assertEqualsWithDelta(1.0, $score['f1'], 1e-9);
        $this->assertSame(2, $score['matchedRows']);
    }

    public function testNumericToleranceMatchesCloseValues(): void
    {
        $scorer = new ExtractionScorer(0.1); // 10%
        $extracted = [
            ['host' => 'CB[7]', 'guest' => 'adamantane', 'ka' => '1.05e9', 'guest_host_ratio' => '1:1'],
            ['host' => 'beta-CD', 'guest' => 'ferrocene', 'ka' => '3000', 'guest_host_ratio' => '1:1'],
        ];
        $score = $scorer->scorePublication($this->gold(), $extracted);
        // 1.05e9 is within 10% of 1.0e9 -> still a perfect match
        $this->assertEqualsWithDelta(1.0, $score['f1'], 1e-9);
    }

    public function testNumericToleranceRejectsFarValues(): void
    {
        $scorer = new ExtractionScorer(0.1);
        $extracted = [
            ['host' => 'CB[7]', 'guest' => 'adamantane', 'ka' => '5.0e9', 'guest_host_ratio' => '1:1'],
            ['host' => 'beta-CD', 'guest' => 'ferrocene', 'ka' => '3000', 'guest_host_ratio' => '1:1'],
        ];
        $score = $scorer->scorePublication($this->gold(), $extracted);
        $this->assertLessThan(1.0, $score['f1']);
    }

    public function testWorseExtractionScoresLower(): void
    {
        $scorer = new ExtractionScorer();
        $good = [
            ['host' => 'CB[7]', 'guest' => 'adamantane', 'ka' => '1.0e9', 'guest_host_ratio' => '1:1'],
            ['host' => 'beta-CD', 'guest' => 'ferrocene', 'ka' => '3000', 'guest_host_ratio' => '2:1'],
        ];
        $bad = [
            ['host' => 'wrong', 'guest' => 'adamantane', 'ka' => '', 'guest_host_ratio' => ''],
        ];
        $goodScore = $scorer->scorePublication($this->gold(), $good);
        $badScore = $scorer->scorePublication($this->gold(), $bad);
        $this->assertGreaterThan($badScore['f1'], $goodScore['f1']);
    }

    public function testMissingRowReducesRecall(): void
    {
        $scorer = new ExtractionScorer();
        $extracted = [
            ['host' => 'CB[7]', 'guest' => 'adamantane', 'ka' => '1.0e9', 'guest_host_ratio' => '1:1'],
        ];
        $score = $scorer->scorePublication($this->gold(), $extracted);
        $this->assertLessThan(1.0, $score['recall']);
        // the one extracted row is fully correct -> precision stays at 1.0
        $this->assertEqualsWithDelta(1.0, $score['precision'], 1e-9);
    }

    public function testAggregateMicroAverages(): void
    {
        $scorer = new ExtractionScorer();
        $s1 = $scorer->scorePublication($this->gold(), $this->gold());
        $s2 = $scorer->scorePublication($this->gold(), $this->gold());
        $agg = $scorer->aggregate([$s1, $s2]);
        $this->assertEqualsWithDelta(1.0, $agg['f1'], 1e-9);
        $this->assertArrayHasKey('perField', $agg);
    }
}
