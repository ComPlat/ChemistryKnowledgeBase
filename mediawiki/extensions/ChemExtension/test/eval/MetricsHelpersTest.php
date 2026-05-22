<?php

namespace DIQA\ChemExtension\Eval;

use PHPUnit\Framework\TestCase;

class MetricsHelpersTest extends TestCase
{
    public function testCosineSimilarityOfIdenticalVectorsIsOne(): void
    {
        $v = [0.1, 0.2, 0.3, 0.4];
        $this->assertEqualsWithDelta(1.0, EmbeddingClient::cosineSimilarity($v, $v), 1e-9);
    }

    public function testCosineSimilarityOfOrthogonalVectorsIsZero(): void
    {
        $this->assertEqualsWithDelta(0.0, EmbeddingClient::cosineSimilarity([1, 0], [0, 1]), 1e-9);
    }

    public function testCosineSimilarityHandlesEmptyVectors(): void
    {
        $this->assertSame(0.0, EmbeddingClient::cosineSimilarity([], [1, 2, 3]));
    }

    public function testStripCsvBlocksRemovesFencedTable(): void
    {
        $text = "Intro prose.\n\n```csv\na , b\n1 , 2\n```\n\nClosing prose.";
        $stripped = ProseSimilarityScorer::stripCsvBlocks($text);
        $this->assertStringContainsString('Intro prose.', $stripped);
        $this->assertStringContainsString('Closing prose.', $stripped);
        $this->assertStringNotContainsString('csv', $stripped);
        $this->assertStringNotContainsString('1 , 2', $stripped);
    }
}
