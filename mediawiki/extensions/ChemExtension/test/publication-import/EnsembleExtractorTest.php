<?php

namespace DIQA\ChemExtension\PublicationImport;

use Exception;
use PHPUnit\Framework\TestCase;

/**
 * EnsembleExtractor must work with any AIClientInterface implementation (OpenAI, KIT, local).
 */
class EnsembleExtractorTest extends TestCase
{
    private ?string $savedServerPath;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/ce-ensemble-test-' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir . '/extensions/ChemExtension', 0777, true);
        mkdir($this->tmpDir . '/logs', 0777, true);
        $this->savedServerPath = $GLOBALS['wgWikiServerPath'] ?? null;
        $GLOBALS['wgWikiServerPath'] = $this->tmpDir;
    }

    protected function tearDown(): void
    {
        $GLOBALS['wgWikiServerPath'] = $this->savedServerPath;
        exec('rm -rf ' . escapeshellarg($this->tmpDir));
    }

    public function testSinglePassUsesCallAI(): void
    {
        $extractor = new EnsembleExtractor(new AIClientMock());
        $this->assertSame('callAI response', $extractor->extract(['f'], 'prompt', 1));
    }

    public function testMergesSuccessfulPassesAndSkipsFailures(): void
    {
        $csv = "== Investigation ==\n```csv\ncatalyst,TON\nFe-1,10\n```";
        $client = new class($csv) extends AIClientMock {
            private string $csv;

            public function __construct(string $csv)
            {
                $this->csv = $csv;
            }

            public function callAIParallel(array $fileIds, string $prompt, int $n, array $imageFileIds = []): array
            {
                return [
                    ['text' => $this->csv, 'error' => null],
                    ['text' => '', 'error' => 'HTTP 503'],
                    ['text' => $this->csv, 'error' => null],
                ];
            }
        };

        $merged = (new EnsembleExtractor($client))->extract(['f'], 'prompt', 3);

        $this->assertStringContainsString('Fe-1', $merged);
    }

    public function testThrowsWhenAllPassesFail(): void
    {
        $client = new class extends AIClientMock {
            public function callAIParallel(array $fileIds, string $prompt, int $n, array $imageFileIds = []): array
            {
                return array_fill(0, $n, ['text' => '', 'error' => 'HTTP 401']);
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('mock');
        (new EnsembleExtractor($client))->extract(['f'], 'prompt', 2);
    }
}
