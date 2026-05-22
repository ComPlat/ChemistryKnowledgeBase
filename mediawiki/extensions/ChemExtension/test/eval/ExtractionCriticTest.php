<?php

namespace DIQA\ChemExtension\Eval;

use PHPUnit\Framework\TestCase;

class ExtractionCriticTest extends TestCase
{
    public function testParseReviewMapsConfidencesAndFlags(): void
    {
        $json = json_encode(['reviews' => [
            ['index' => 0, 'confidence' => 0.9, 'evidence' => 'see Table 1'],
            ['index' => 1, 'confidence' => 0.3, 'evidence' => ''],
        ]]);

        $result = ExtractionCritic::parseReview($json, 3, 0.6);

        $this->assertSame(0.9, $result['rowConfidences'][0]);
        $this->assertSame(0.3, $result['rowConfidences'][1]);
        $this->assertSame(0.0, $result['rowConfidences'][2]); // not reviewed -> 0
        $this->assertSame([1, 2], $result['flagged']);
        $this->assertEqualsWithDelta(0.4, $result['avgConfidence'], 1e-9);
        $this->assertSame('see Table 1', $result['evidence'][0]);
    }

    public function testParseReviewClampsConfidence(): void
    {
        $json = json_encode(['reviews' => [
            ['index' => 0, 'confidence' => 1.5, 'evidence' => null],
            ['index' => 1, 'confidence' => -0.2, 'evidence' => null],
        ]]);
        $result = ExtractionCritic::parseReview($json, 2, 0.6);
        $this->assertSame(1.0, $result['rowConfidences'][0]);
        $this->assertSame(0.0, $result['rowConfidences'][1]);
    }

    public function testParseReviewHandlesGarbage(): void
    {
        $result = ExtractionCritic::parseReview('not json', 2, 0.6);
        $this->assertSame([0.0, 0.0], $result['rowConfidences']);
        $this->assertSame([0, 1], $result['flagged']);
    }
}
