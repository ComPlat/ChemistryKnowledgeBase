<?php

namespace DIQA\ChemExtension\PublicationImport;

use DIQA\ChemExtension\Utils\LoggerUtils;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Chat Completions client (KIT KI-Toolbox, self-hosted models) against a scripted
 * transport. No network access.
 */
class ChatCompletionsAIClientTest extends TestCase
{
    /** Error returned by vLLM-served models on KIT when a PDF file part is sent. */
    private const VLLM_FILE_ERROR = 'HTTP 400: {"error":{"message":"litellm.BadRequestError: Hosted_vllmException - '
        . '{\"error\":{\"message\":\"Unsupported chat content part type: \'file\'. Supported types: image_url, '
        . 'input_text, text (parameter=type, value=file)\",\"type\":\"BadRequestError\"}}"}}';

    private string $tmpDir;
    private ?string $savedServerPath;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/ce-ai-test-' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir . '/wiki/extensions/ChemExtension', 0777, true);
        mkdir($this->tmpDir . '/wiki/logs', 0777, true);
        $this->savedServerPath = $GLOBALS['wgWikiServerPath'] ?? null;
        $GLOBALS['wgWikiServerPath'] = $this->tmpDir . '/wiki';
    }

    protected function tearDown(): void
    {
        $GLOBALS['wgWikiServerPath'] = $this->savedServerPath;
        exec('rm -rf ' . escapeshellarg($this->tmpDir));
    }

    public function testBuildRequestBodySplitsSystemAndTask(): void
    {
        $body = ChatCompletionsAIClient::buildRequestBody(
            'kit.qwen', 'low',
            "[SYSTEM-LIKE INSTRUCTIONS]\nYou extract data.\n[TASK]\nExtract the table.",
            [['type' => 'text', 'text' => 'DOC']],
            ['type' => 'json_schema', 'json_schema' => ['name' => 'x', 'strict' => true, 'schema' => []]]
        );

        $this->assertSame('kit.qwen', $body['model']);
        $this->assertSame('low', $body['reasoning_effort']);
        $this->assertSame(['role' => 'system', 'content' => 'You extract data.'], $body['messages'][0]);
        $this->assertSame('user', $body['messages'][1]['role']);
        $this->assertSame([
            ['type' => 'text', 'text' => 'DOC'],
            ['type' => 'text', 'text' => 'Extract the table.'],
        ], $body['messages'][1]['content']);
        $this->assertSame('json_schema', $body['response_format']['type']);
    }

    public function testBuildRequestBodyWithoutSystemOrEffort(): void
    {
        $body = ChatCompletionsAIClient::buildRequestBody('m', null, 'Just a prompt', []);
        $this->assertCount(1, $body['messages']);
        $this->assertArrayNotHasKey('reasoning_effort', $body);
        $this->assertArrayNotHasKey('response_format', $body);
        $this->assertSame('Just a prompt', $body['messages'][0]['content'][0]['text']);
    }

    public function testExtractTextStripsReasoningTrace(): void
    {
        $response = ['choices' => [['message' => ['content' => "<think>\nlet me see\n</think>\n\n== Summary ==\nok"]]]];
        $this->assertSame("== Summary ==\nok", ChatCompletionsAIClient::extractText($response));
    }

    public function testExtractTextHandlesContentPartsAndMissingContent(): void
    {
        $parts = ['choices' => [['message' => ['content' => [['type' => 'text', 'text' => 'a'], ['type' => 'text', 'text' => 'b']]]]]];
        $this->assertSame('ab', ChatCompletionsAIClient::extractText($parts));
        $this->assertSame('', ChatCompletionsAIClient::extractText(['choices' => []]));
        $this->assertSame('', ChatCompletionsAIClient::extractText(null));
    }

    public function testExtractUsage(): void
    {
        $this->assertSame(['input' => 10, 'output' => 5, 'total' => 15],
            ChatCompletionsAIClient::extractUsage(['usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15]]));
        $this->assertNull(ChatCompletionsAIClient::extractUsage([]));
    }

    public function testDetectsUnsupportedFileInputError(): void
    {
        $this->assertTrue(ChatCompletionsAIClient::isUnsupportedFileInputError(self::VLLM_FILE_ERROR));
        $this->assertFalse(ChatCompletionsAIClient::isUnsupportedFileInputError('HTTP 401: invalid api key'));
        $this->assertFalse(ChatCompletionsAIClient::isUnsupportedFileInputError('HTTP 429: rate limit'));
    }

    public function testNativePdfIsSentAsBase64FilePart(): void
    {
        $transport = $this->transport([self::completion('result')]);
        $client = $this->client('azure.gpt-5.4', $transport);
        $pdf = $this->writeFile('paper.pdf', '%PDF-1.4 fake');

        $ids = $client->uploadFiles([$pdf]);
        $result = $client->callAI($ids, 'Extract');

        $this->assertSame('result', $result);
        $part = $transport->bodies[0]['messages'][0]['content'][0];
        $this->assertSame('file', $part['type']);
        $this->assertSame('paper.pdf', $part['file']['filename']);
        $this->assertSame('data:application/pdf;base64,' . base64_encode('%PDF-1.4 fake'), $part['file']['file_data']);
        $this->assertSame(['input' => 3, 'output' => 2, 'total' => 5], $client->getLastUsage());
    }

    public function testTextModeSendsExtractedTextAndImages(): void
    {
        $transport = $this->transport([self::completion('ok')]);
        $client = $this->client('kit.mistral-small-4-119b-a8b', $transport, new FakePdfTextExtractor('Table 1: TON 42'));
        $pdf = $this->writeFile('paper.pdf', '%PDF');
        $png = $this->writeFile('page-1.png', 'PNGDATA');

        $client->callAI($client->uploadFiles([$pdf]), 'Extract', $client->uploadFiles([$png]));

        $content = $transport->bodies[0]['messages'][0]['content'];
        $this->assertSame(['type' => 'text', 'text' => "<document name=\"paper.pdf\">\nTable 1: TON 42\n</document>"], $content[0]);
        $this->assertSame(['type' => 'image_url', 'image_url' => ['url' => 'data:image/png;base64,' . base64_encode('PNGDATA')]], $content[1]);
        $this->assertSame(['type' => 'text', 'text' => 'Extract'], $content[2]);
    }

    public function testFallsBackToTextWhenModelRejectsPdfFiles(): void
    {
        $transport = $this->transport([
            new AIRequestException(self::VLLM_FILE_ERROR, 400),
            self::completion('from text'),
            self::completion('still text'),
        ]);
        $client = $this->client('azure.gpt-5.4', $transport, new FakePdfTextExtractor('PDF TEXT'),
            ['pdfInput' => 'native']);
        $ids = $client->uploadFiles([$this->writeFile('a.pdf', '%PDF')]);

        $this->assertSame('from text', $client->callAI($ids, 'Extract'));
        $this->assertSame('file', $transport->bodies[0]['messages'][0]['content'][0]['type']);
        $this->assertSame('text', $transport->bodies[1]['messages'][0]['content'][0]['type']);

        // the client remembers the fallback instead of failing again
        $client->callAI($ids, 'Extract');
        $this->assertCount(3, $transport->bodies);
        $this->assertSame('text', $transport->bodies[2]['messages'][0]['content'][0]['type']);
    }

    public function testOtherErrorsAreNotSwallowed(): void
    {
        $transport = $this->transport([new AIRequestException('HTTP 401: invalid key', 401)]);
        $client = $this->client('azure.gpt-5.4', $transport);

        $this->expectException(AIRequestException::class);
        $client->callAIWithTextInputs(['abstract'], 'Tag this');
    }

    public function testSchemaRequestUsesResponseFormat(): void
    {
        $transport = $this->transport([self::completion('{"rows":[]}')]);
        $client = $this->client('kit.qwen', $transport);
        $schema = ['type' => 'object', 'properties' => [], 'additionalProperties' => false];

        $this->assertSame('{"rows":[]}', $client->callAIWithSchema([], 'Extract', $schema, 'critic'));
        $this->assertSame(
            ['type' => 'json_schema', 'json_schema' => ['name' => 'critic', 'strict' => true, 'schema' => $schema]],
            $transport->bodies[0]['response_format']
        );
    }

    public function testParallelPassesReportTextAndErrors(): void
    {
        $transport = $this->transport([]);
        $transport->parallelResults = [[
            ['data' => self::completion('pass 1'), 'error' => null],
            ['data' => null, 'error' => 'HTTP 503: overloaded'],
            ['data' => self::completion(''), 'error' => null],
        ]];
        $client = $this->client('kit.qwen', $transport);

        $results = $client->callAIParallel([], 'Extract', 3);

        $this->assertSame([
            ['text' => 'pass 1', 'error' => null],
            ['text' => '', 'error' => 'HTTP 503: overloaded'],
            ['text' => '', 'error' => 'empty output'],
        ], $results);
        $this->assertSame(3, $transport->parallelN[0]);
    }

    public function testParallelFallsBackToTextForPdfs(): void
    {
        $transport = $this->transport([]);
        $transport->parallelResults = [
            array_fill(0, 2, ['data' => null, 'error' => self::VLLM_FILE_ERROR]),
            array_fill(0, 2, ['data' => self::completion('ok'), 'error' => null]),
        ];
        $client = $this->client('azure.gpt-5.4', $transport, new FakePdfTextExtractor('T'));
        $ids = $client->uploadFiles([$this->writeFile('a.pdf', '%PDF')]);

        $results = $client->callAIParallel($ids, 'Extract', 2);

        $this->assertSame('ok', $results[0]['text']);
        $this->assertSame('text', $transport->bodies[1]['messages'][0]['content'][0]['type']);
    }

    public function testTemporaryTextFilesAreRemovedOnDelete(): void
    {
        $transport = $this->transport([self::completion('ok')]);
        $client = $this->client('kit.qwen', $transport);

        $ids = $client->uploadTextAsFile('abstract text');
        $client->callAI($ids, 'Summarize');
        $this->assertStringContainsString('abstract text', $transport->bodies[0]['messages'][0]['content'][0]['text']);

        $client->deleteFiles($ids);
        $this->expectException(\RuntimeException::class);
        $client->callAI($ids, 'Summarize');
    }

    public function testUnreadableFilesAreSkipped(): void
    {
        $client = $this->client('kit.qwen', $this->transport([]));
        $this->assertSame([], $client->uploadFiles([$this->tmpDir . '/missing.pdf']));
    }

    public function testBackoffHonoursRetryAfterAndCaps(): void
    {
        $this->assertSame(7.0, AIHttpTransport::backoffSeconds(0, 7, 0.5));
        $this->assertSame(60.0, AIHttpTransport::backoffSeconds(0, 600, 0.5));
        $this->assertSame(2.0, AIHttpTransport::backoffSeconds(1, null, 0.5));
        $this->assertSame(60.0, AIHttpTransport::backoffSeconds(10, null, 1.0));
        $this->assertSame(0.5, AIHttpTransport::backoffSeconds(0, null, 0.0));
    }

    public function testRetryableStatuses(): void
    {
        foreach ([0, 408, 409, 429, 500, 502, 503] as $status) {
            $this->assertTrue(AIRequestException::isRetryableStatus($status), "status $status");
        }
        foreach ([400, 401, 403, 404, 422] as $status) {
            $this->assertFalse(AIRequestException::isRetryableStatus($status), "status $status");
        }
    }

    private function client(string $model, ScriptedTransport $transport, ?PdfTextExtractor $extractor = null,
                            array $settings = []): ChatCompletionsAIClient
    {
        $config = AIProviderConfig::fromArray('kit', ['model' => $model, 'apiKey' => 'test'] + $settings);
        return new ChatCompletionsAIClient($config, $transport, $extractor ?? new FakePdfTextExtractor(''));
    }

    private function transport(array $responses): ScriptedTransport
    {
        return new ScriptedTransport(AIProviderConfig::fromArray('kit', ['apiKey' => 'test']),
            new LoggerUtils('test', 'ChemExtension'), $responses);
    }

    private function writeFile(string $name, string $content): string
    {
        $path = $this->tmpDir . '/' . $name;
        file_put_contents($path, $content);
        return $path;
    }

    private static function completion(string $text): array
    {
        return [
            'choices' => [['message' => ['role' => 'assistant', 'content' => $text], 'finish_reason' => 'stop']],
            'usage' => ['prompt_tokens' => 3, 'completion_tokens' => 2, 'total_tokens' => 5],
        ];
    }
}

/** Replays scripted responses and records request bodies. */
class ScriptedTransport extends AIHttpTransport
{
    public array $bodies = [];
    public array $parallelN = [];
    public array $parallelResults = [];
    private array $responses;

    public function __construct(AIProviderConfig $config, LoggerUtils $logger, array $responses)
    {
        parent::__construct($config, $logger, static function () {
        });
        $this->responses = $responses;
    }

    public function postJson(string $path, array $body): array
    {
        $this->bodies[] = $body;
        $next = array_shift($this->responses);
        if ($next instanceof \Throwable) {
            throw $next;
        }
        if ($next === null) {
            throw new \LogicException('No scripted response left');
        }
        return $next;
    }

    public function postJsonParallel(string $path, array $body, int $n): array
    {
        $this->bodies[] = $body;
        $this->parallelN[] = $n;
        return array_shift($this->parallelResults);
    }
}

class FakePdfTextExtractor extends PdfTextExtractor
{
    private string $text;

    public function __construct(string $text)
    {
        parent::__construct('unused');
        $this->text = $text;
    }

    public function extract(string $pdfPath): string
    {
        return $this->text;
    }
}
