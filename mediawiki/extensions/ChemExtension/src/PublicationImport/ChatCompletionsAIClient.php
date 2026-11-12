<?php

namespace DIQA\ChemExtension\PublicationImport;

use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;
use RuntimeException;

/**
 * {@see AIClientInterface} for OpenAI-compatible Chat Completions backends: the KIT
 * KI-Toolbox and self-hosted servers (vLLM, Ollama, LM Studio, llama.cpp, ...).
 *
 * These backends have no Files API, so "uploading" registers a local file and every request
 * carries the document inline:
 *  - PDF, native mode: base64 `file` content part (models proxied to Azure/Google on KIT)
 *  - PDF, text mode:   text extracted locally with pdftotext (vLLM/Ollama models, which
 *                      only accept text and images)
 *  - images:           base64 `image_url` parts (vision input, e.g. rendered PDF pages)
 *  - text files:       plain text parts
 *
 * If a backend rejects native PDF input, the client falls back to text mode once and keeps
 * using it for its lifetime, so a misconfigured `pdfInput` degrades instead of failing.
 */
class ChatCompletionsAIClient implements AIClientInterface
{
    private const FILE_ID_PREFIX = 'local-';
    private const IMAGE_EXTENSIONS = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp', 'gif' => 'image/gif'];

    private AIProviderConfig $config;
    private AIHttpTransport $http;
    private PdfTextExtractor $pdfTextExtractor;
    private LoggerUtils $logger;
    private string $pdfInputMode;
    private ?array $lastUsage = null;

    /** @var array<string, array{path: string, name: string, kind: string, temporary: bool}> */
    private array $files = [];
    /** @var array<string, string> path => extracted text */
    private array $pdfTextCache = [];

    public function __construct(AIProviderConfig $config, ?AIHttpTransport $http = null,
                                ?PdfTextExtractor $pdfTextExtractor = null)
    {
        $this->config = $config;
        $this->logger = new LoggerUtils('ChatCompletionsAIClient', 'ChemExtension');
        $this->http = $http ?? new AIHttpTransport($config, $this->logger);
        $this->pdfTextExtractor = $pdfTextExtractor ?? new PdfTextExtractor();
        $this->pdfInputMode = $config->getPdfInputMode();
    }

    public function describe(): string
    {
        return $this->config->describe();
    }

    public function ping(): array
    {
        try {
            $response = $this->http->postJson('chat/completions', self::buildRequestBody(
                $this->config->getModel(), null, 'Reply with: ok', []
            ));
            if (!isset($response['choices'][0])) {
                $msg = sprintf('%s ping failed: unexpected response (no choices).', $this->describe());
                $this->logger->error($msg . ' Response: ' . json_encode($response));
                return ['ok' => false, 'message' => $msg];
            }
            $this->logger->log(sprintf('%s ping successful.', $this->describe()));
            return ['ok' => true, 'message' => sprintf('%s is reachable and accepting requests.', $this->describe())];
        } catch (Exception $e) {
            $msg = sprintf('%s ping failed: %s', $this->describe(), $e->getMessage());
            $this->logger->error($msg);
            return ['ok' => false, 'message' => $msg];
        }
    }

    public function uploadFiles(array $files): array
    {
        $ids = [];
        foreach ($files as $path) {
            if (!is_file($path) || !is_readable($path)) {
                $this->logger->error("Cannot read file for AI request: $path");
                continue;
            }
            $ids[] = $this->register($path, self::detectKind($path), false);
        }
        $this->logger->log("Registered files for AI requests: " . implode(', ', $files));
        return $ids;
    }

    public function uploadTextAsFile($text): array
    {
        $path = tempnam(sys_get_temp_dir(), 'ai-text-');
        if ($path === false || file_put_contents($path, (string)$text) === false) {
            throw new RuntimeException('Could not write temporary text file for AI request');
        }
        return [$this->register($path, 'text', true)];
    }

    public function deleteFiles(array $files): void
    {
        foreach ($files as $id) {
            $file = $this->files[$id] ?? null;
            if ($file === null) {
                continue;
            }
            if ($file['temporary']) {
                @unlink($file['path']);
            }
            unset($this->pdfTextCache[$file['path']], $this->files[$id]);
        }
    }

    public function callAI(array $fileIds, string $prompt, array $imageFileIds = []): string
    {
        $this->logger->log(sprintf("Request to %s with prompt: '%s' and documents [%s]",
            $this->describe(), $prompt, implode(',', $fileIds)));
        $result = $this->complete(fn() => $this->buildFileContent($fileIds, $imageFileIds), $prompt);
        $this->logger->log("Response from AI: " . $result);
        return $result;
    }

    public function callAIWithTextInputs(array $textInputs, string $prompt): string
    {
        $this->logger->log(sprintf("Request to %s with prompt: '%s' and %d text input(s)",
            $this->describe(), $prompt, count($textInputs)));
        $content = array_map(static fn($text) => ['type' => 'text', 'text' => (string)$text], $textInputs);
        $result = $this->complete(fn() => $content, $prompt);
        $this->logger->log("Response from AI: " . $result);
        return $result;
    }

    public function callAIWithSchema(array $fileIds, string $prompt, array $jsonSchema,
                                     string $schemaName = 'extraction', array $imageFileIds = []): string
    {
        $this->logger->log(sprintf("Structured request to %s with prompt: '%s' and documents [%s]",
            $this->describe(), $prompt, implode(',', $fileIds)));
        $responseFormat = [
            'type' => 'json_schema',
            'json_schema' => ['name' => $schemaName, 'strict' => true, 'schema' => $jsonSchema],
        ];
        $result = $this->complete(fn() => $this->buildFileContent($fileIds, $imageFileIds), $prompt, $responseFormat);
        $this->logger->log("Structured response from AI: " . $result);
        return $result;
    }

    public function callAIParallel(array $fileIds, string $prompt, int $n, array $imageFileIds = []): array
    {
        $run = function () use ($fileIds, $imageFileIds, $prompt, $n): array {
            $body = self::buildRequestBody($this->config->getModel(), $this->config->getReasoningEffort(),
                $prompt, $this->buildFileContent($fileIds, $imageFileIds));
            return $this->http->postJsonParallel('chat/completions', $body, $n);
        };

        $responses = $run();
        if ($this->shouldFallBackToText($fileIds, array_column($responses, 'error'))) {
            $responses = $run();
        }

        return array_map(function (array $response): array {
            if ($response['error'] !== null) {
                return ['text' => '', 'error' => $response['error']];
            }
            $text = self::extractText($response['data']);
            return ['text' => $text, 'error' => $text === '' ? 'empty output' : null];
        }, $responses);
    }

    public function getLastUsage(): ?array
    {
        return $this->lastUsage;
    }

    /**
     * Assembles a Chat Completions request. The prompt may contain the sections
     * [SYSTEM-LIKE INSTRUCTIONS] and [TASK] (see {@see AIClient::splitByTags()}); the former
     * becomes the system message, the latter is appended after the documents.
     *
     * @param array      $userContent    content parts (documents/images) preceding the prompt
     * @param array|null $responseFormat OpenAI `response_format` for structured outputs
     */
    public static function buildRequestBody(string $model, ?string $reasoningEffort, string $prompt,
                                            array $userContent, ?array $responseFormat = null): array
    {
        $parts = AIClient::splitByTags($prompt);
        $userPrompt = $parts['task'] === '' ? $parts['rest'] : $parts['task'];
        $userContent[] = ['type' => 'text', 'text' => $userPrompt];

        $messages = [];
        if ($parts['systemLikeInstructions'] !== '') {
            $messages[] = ['role' => 'system', 'content' => $parts['systemLikeInstructions']];
        }
        $messages[] = ['role' => 'user', 'content' => $userContent];

        $body = ['model' => $model, 'messages' => $messages];
        if ($reasoningEffort !== null) {
            $body['reasoning_effort'] = $reasoningEffort;
        }
        if ($responseFormat !== null) {
            $body['response_format'] = $responseFormat;
        }
        return $body;
    }

    /**
     * Returns the assistant text of a Chat Completions response. Reasoning traces that some
     * open-weight models emit inline (`<think>…</think>`) are removed, since downstream
     * parsers expect only the answer.
     */
    public static function extractText(?array $response): string
    {
        $content = $response['choices'][0]['message']['content'] ?? '';
        if (is_array($content)) {
            $content = implode('', array_map(
                static fn($part) => is_array($part) ? (string)($part['text'] ?? '') : (string)$part,
                $content
            ));
        }
        $content = preg_replace('/^\s*<think>.*?<\/think>/s', '', (string)$content);
        return trim($content);
    }

    /**
     * @return array{input:int, output:int, total:int}|null
     */
    public static function extractUsage(array $response): ?array
    {
        $usage = $response['usage'] ?? null;
        if (!is_array($usage)) {
            return null;
        }
        $input = (int)($usage['prompt_tokens'] ?? 0);
        $output = (int)($usage['completion_tokens'] ?? 0);
        return ['input' => $input, 'output' => $output, 'total' => (int)($usage['total_tokens'] ?? $input + $output)];
    }

    /**
     * Whether an error message says the backend does not accept PDF `file` content parts
     * (vLLM: "Unsupported chat content part type: 'file'").
     */
    public static function isUnsupportedFileInputError(string $message): bool
    {
        return (bool)preg_match(
            "/content part type:?\s*\\\\?['\"]file\\\\?['\"]|does not support (file|pdf|document)|file_data/i",
            $message
        );
    }

    /**
     * Content parts for the given file ids, in order: documents first, then images.
     */
    private function buildFileContent(array $fileIds, array $imageFileIds = []): array
    {
        $content = [];
        foreach (array_merge($fileIds, $imageFileIds) as $id) {
            $file = $this->files[$id] ?? null;
            if ($file === null) {
                throw new RuntimeException("Unknown file id '$id' (already deleted?)");
            }
            $content[] = $this->contentPart($file);
        }
        return $content;
    }

    /**
     * @param array{path: string, name: string, kind: string, temporary: bool} $file
     */
    private function contentPart(array $file): array
    {
        switch ($file['kind']) {
            case 'image':
                return ['type' => 'image_url', 'image_url' => ['url' => self::dataUri($file['path'], self::mimeType($file['path']))]];
            case 'pdf':
                if ($this->pdfInputMode === AIProviderConfig::PDF_INPUT_NATIVE) {
                    return ['type' => 'file', 'file' => [
                        'filename' => $file['name'],
                        'file_data' => self::dataUri($file['path'], 'application/pdf'),
                    ]];
                }
                return self::documentText($file['name'], $this->pdfText($file['path']));
            default:
                return self::documentText($file['name'], (string)file_get_contents($file['path']));
        }
    }

    private function pdfText(string $path): string
    {
        if (!isset($this->pdfTextCache[$path])) {
            $this->pdfTextCache[$path] = $this->pdfTextExtractor->extract($path);
            $this->logger->log(sprintf("Extracted %d characters of text from %s", strlen($this->pdfTextCache[$path]), $path));
        }
        return $this->pdfTextCache[$path];
    }

    /**
     * Sends a single request. $buildContent is a closure so the content can be rebuilt after a
     * native-PDF → text fallback.
     */
    private function complete(callable $buildContent, string $prompt, ?array $responseFormat = null): string
    {
        $send = fn() => $this->http->postJson('chat/completions', self::buildRequestBody(
            $this->config->getModel(), $this->config->getReasoningEffort(), $prompt, $buildContent(), $responseFormat
        ));

        try {
            $response = $send();
        } catch (AIRequestException $e) {
            if (!$this->switchToTextModeOn($e->getMessage())) {
                throw $e;
            }
            $response = $send();
        }

        $this->lastUsage = self::extractUsage($response);
        if (($response['choices'][0]['finish_reason'] ?? null) === 'length') {
            $this->logger->warn(sprintf('%s: output truncated (finish_reason=length)', $this->describe()));
        }
        return self::extractText($response);
    }

    /**
     * @param string[]      $fileIds
     * @param (string|null)[] $errors
     */
    private function shouldFallBackToText(array $fileIds, array $errors): bool
    {
        $hasPdf = false;
        foreach ($fileIds as $id) {
            $hasPdf = $hasPdf || (($this->files[$id]['kind'] ?? null) === 'pdf');
        }
        if (!$hasPdf) {
            return false;
        }
        foreach ($errors as $error) {
            if ($error !== null && $this->switchToTextModeOn($error)) {
                return true;
            }
        }
        return false;
    }

    private function switchToTextModeOn(string $errorMessage): bool
    {
        if ($this->pdfInputMode !== AIProviderConfig::PDF_INPUT_NATIVE || !self::isUnsupportedFileInputError($errorMessage)) {
            return false;
        }
        $this->logger->warn(sprintf("%s does not accept PDF files; falling back to extracted text. "
            . "Set pdfInput => 'text' for this provider to skip the failed attempt.", $this->describe()));
        $this->pdfInputMode = AIProviderConfig::PDF_INPUT_TEXT;
        return true;
    }

    private function register(string $path, string $kind, bool $temporary): string
    {
        $id = self::FILE_ID_PREFIX . bin2hex(random_bytes(8));
        $this->files[$id] = ['path' => $path, 'name' => basename($path), 'kind' => $kind, 'temporary' => $temporary];
        return $id;
    }

    private static function detectKind(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'pdf') {
            return 'pdf';
        }
        return isset(self::IMAGE_EXTENSIONS[$extension]) ? 'image' : 'text';
    }

    private static function mimeType(string $path): string
    {
        return self::IMAGE_EXTENSIONS[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
    }

    private static function dataUri(string $path, string $mimeType): string
    {
        $data = file_get_contents($path);
        if ($data === false) {
            throw new RuntimeException("Cannot read file: $path");
        }
        return "data:$mimeType;base64," . base64_encode($data);
    }

    private static function documentText(string $name, string $text): array
    {
        $name = htmlspecialchars($name, ENT_QUOTES);
        return ['type' => 'text', 'text' => "<document name=\"$name\">\n$text\n</document>"];
    }
}
