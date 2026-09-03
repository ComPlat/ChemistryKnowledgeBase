<?php

namespace DIQA\ChemExtension\PublicationImport;

use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;
use OpenAI;

class AIClient implements AIClientInterface
{

    private OpenAI\Client $client;
    private LoggerUtils $logger;
    private $lastUsage = null;

    static function getAIClient(): AIClientInterface {
        global $wgCEUseAIClientMock;
        return isset($wgCEUseAIClientMock) && $wgCEUseAIClientMock === true ? new AIClientMock() : new AIClient();
    }

    private function __construct()
    {
        global $wgOpenAIKey;
        if (!isset($wgOpenAIKey)) {
            throw new Exception('OpenAI-Key is missing. please configure $wgOpenAIKey');
        }
        $yourApiKey = $wgOpenAIKey;
        $this->logger = new LoggerUtils('AIClient', 'ChemExtension');
        $this->client = OpenAI::factory()
            ->withApiKey($yourApiKey)
            ->make();
    }

    /**
     * Checks whether the OpenAI client is operational and requests are accepted
     * (e.g. not refused due to an empty budget, invalid key, exceeded quota, etc.).
     *
     * Performs a minimal, low-cost request and inspects the response. On any
     * failure, the underlying error is logged and false is returned.
     *
     * @return array{ok: bool, message: string} Status info: ok=true on success,
     *                                          message contains details on failure.
     */
    public function ping(): array
    {
        try {
            global $wgOpenAIModel;
            $model = $wgOpenAIModel ?? 'o3';

            $response = $this->client->responses()->create([
                'model' => $model,
                'reasoning' => ['effort' => 'low'],
                'input' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => 'ping',
                            ],
                        ],
                    ],
                ],
                'max_output_tokens' => 16,
            ]);

            if (!isset($response->id)) {
                $msg = 'OpenAI ping failed: unexpected response (no id returned).';
                $this->logger->error($msg . ' Response: ' . print_r($response, true));
                return ['ok' => false, 'message' => $msg];
            }

            $this->logger->log("OpenAI ping successful (response id: {$response->id}).");
            return ['ok' => true, 'message' => 'OpenAI client is reachable and accepting requests.'];

        } catch (Exception $e) {
            $msg = 'OpenAI ping failed: ' . $e->getMessage();
            $this->logger->error($msg);
            return ['ok' => false, 'message' => $msg];
        }
    }


    public function uploadFiles(array $files): array
    {
        $this->logger->log("uploading files to AI: " . join(', ', $files));
        $ids = [];

        foreach ($files as $file) {
            try {
                $fileHandle = fopen($file, 'r');
                $response = $this->client->files()->upload([
                    'purpose' => 'user_data',
                    'file' => $fileHandle,
                ]);
                if (!isset($response->id) || $response->status !== 'processed') {
                    $this->logger->error("Could not upload file to OpenAI: $file\n" . print_r($response, true));
                    continue;
                }
                $ids[] = $response->id;
                $this->logger->log(sprintf("Uploaded file: %s, id: %s", $response->filename, $response->id));
            } catch (Exception $e) {
                $this->logger->error("Could not upload file to OpenAI: $file");
                $this->logger->error($e->getMessage());
            }
        }
        return $ids;
    }

    public function uploadTextAsFile($text): array
    {
        $ids = [];

        $file = tempnam(sys_get_temp_dir(), 'text') . ".txt";
        file_put_contents($file, $text);
        print $text;
        $fileHandle = fopen($file, 'r');
        $response = $this->client->files()->upload([
            'purpose' => 'user_data',
            'file' => $fileHandle,
        ]);
        if (!isset($response->id) || $response->status !== 'processed') {
            $msg = "Could not upload file to OpenAI: $file\n" . print_r($response, true);
            $this->logger->error($msg);
            throw new Exception($msg);
        }
        $ids[] = $response->id;
        $this->logger->log(sprintf("Uploaded file: %s, id: %s", $response->filename, $response->id));

        return $ids;
    }

    /**
     * Builds the user content array from uploaded document file ids and (optional) image file ids
     * (rendered PDF pages) for vision input. Public so EnsembleExtractor can construct a
     * request body identical to what callAI would send, then fire N parallel POSTs itself.
     */
    public function buildFileContent(array $fileIds, array $imageFileIds = []): array
    {
        $content = array_map(fn($fileId) => ["type" => "input_file", "file_id" => $fileId], $fileIds);
        foreach ($imageFileIds as $imageId) {
            $content[] = ["type" => "input_image", "file_id" => $imageId];
        }
        return $content;
    }

    public function deleteFiles(array $files): void
    {
        foreach ($files as $fileId) {
            $response = $this->client->files()->delete($fileId);
            if (!$response->deleted) {
                $this->logger->warn("File could not be deleted in OpenAI repo: $fileId");
            } else {
                $this->logger->log("File deleted from OpenAI repo: $fileId");
            }
        }
    }

    public function callAI(array $fileIds, string $prompt, array $imageFileIds = []): string
    {
        $this->logger->log("Request to AI with prompt: '$prompt' and documents [" . join($fileIds) . "]");
        $userContent = $this->buildFileContent($fileIds, $imageFileIds);
        $parameters = $this->extractRequestParameters($prompt, $userContent);
        $response = $this->client->responses()->create($parameters);
        $result = $response->outputText ?? 'no output generated';
        $this->lastUsage = $this->extractUsage($response);
        $this->logger->log("Response from AI: " . $result);
        return $result;
    }

    /**
     * Like callAI(), but constrains the model to a JSON schema (OpenAI structured outputs).
     * Returns the raw JSON string. Eliminates CSV parse / column-drift failures.
     *
     * @param string[] $fileIds
     * @param array    $jsonSchema a JSON-schema object (see Eval\TopicSchema)
     */
    public function callAIWithSchema(array $fileIds, string $prompt, array $jsonSchema, string $schemaName = 'extraction', array $imageFileIds = []): string
    {
        $this->logger->log("Structured request to AI with prompt: '$prompt' and documents [" . join(',', $fileIds) . "]");
        $userContent = $this->buildFileContent($fileIds, $imageFileIds);
        $parameters = $this->extractRequestParameters($prompt, $userContent);
        $parameters['text'] = [
            'format' => [
                'type' => 'json_schema',
                'name' => $schemaName,
                'strict' => true,
                'schema' => $jsonSchema,
            ],
        ];
        $response = $this->client->responses()->create($parameters);
        $result = $response->outputText ?? '';
        $this->lastUsage = $this->extractUsage($response);
        $this->logger->log("Structured response from AI: " . $result);
        return $result;
    }

    public function callAIWithTextInputs(array $textInputs, string $prompt): string
    {
        $this->logger->log("Request to AI with prompt: '$prompt' and documents [" . join($textInputs) . "]");
        $userContent = array_map(fn($text) => ["type" => "input_text", "text" => $text], $textInputs);
        $parameters = $this->extractRequestParameters($prompt, $userContent);
        $response = $this->client->responses()->create($parameters);
        $result = $response->outputText ?? 'no output generated';
        $this->lastUsage = $this->extractUsage($response);
        $this->logger->log("Response from AI: " . $result);
        return $result;
    }

    private const OPENAI_RESPONSES_URL = 'https://api.openai.com/v1/responses';
    private const REQUEST_TIMEOUT_SECONDS = 600;

    /**
     * Fire $n identical HTTP POST requests to the OpenAI Responses endpoint concurrently
     * using curl_multi. Returns an array of decoded JSON responses (one per handle, in the
     * original order). Requests that error out have an "_error" key set instead of output.
     *
     * Wall-time is ~max(request_time), not sum — that's the whole point of running parallel.
     *
     * @param array $requestBody Body identical for every pass
     * @param int   $n           Number of concurrent requests
     * @return array<int, array>
     */
    public function parallelPost(array $requestBody, int $n): array
    {
        global $wgOpenAIKey;
        if (empty($wgOpenAIKey)) {
            throw new Exception('OpenAI-Key is missing. please configure $wgOpenAIKey');
        }
        $jsonBody = json_encode($requestBody);
        if ($jsonBody === false) {
            throw new Exception('failed to encode request body for parallel POST');
        }

        $multi = curl_multi_init();
        $handles = [];
        for ($i = 0; $i < $n; $i++) {
            $ch = curl_init(self::OPENAI_RESPONSES_URL);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonBody,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $wgOpenAIKey,
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT_SECONDS,
                CURLOPT_CONNECTTIMEOUT => 30,
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[$i] = $ch;
        }

        // Drive the multi handle until all requests complete.
        $running = 0;
        do {
            $status = curl_multi_exec($multi, $running);
            if ($running > 0) {
                curl_multi_select($multi, 1.0);
            }
        } while ($running > 0 && $status === CURLM_OK);

        $responses = [];
        foreach ($handles as $i => $ch) {
            $body = curl_multi_getcontent($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErrno = curl_errno($ch);
            if ($curlErrno !== 0) {
                $responses[$i] = ['_error' => 'curl error: ' . curl_error($ch)];
            } elseif ($httpCode !== 200) {
                // Preserve the error message for logging, but leave output empty so merger skips.
                $snippet = is_string($body) ? substr($body, 0, 500) : '';
                $responses[$i] = ['_error' => "HTTP $httpCode: $snippet"];
            } else {
                $decoded = json_decode((string)$body, true);
                $responses[$i] = is_array($decoded) ? $decoded : ['_error' => 'malformed json'];
            }
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }
        curl_multi_close($multi);
        return $responses;
    }

    /**
     * Token usage of the most recent callAI / callAIWithTextInputs request.
     *
     * @return array{input:int, output:int, total:int}|null
     */
    public function getLastUsage(): ?array
    {
        return $this->lastUsage;
    }

    private function extractUsage($response): ?array
    {
        $usage = $response->usage ?? null;
        if ($usage === null) {
            return null;
        }
        return [
            'input' => $usage->inputTokens ?? 0,
            'output' => $usage->outputTokens ?? 0,
            'total' => $usage->totalTokens ?? 0,
        ];
    }

    /**
     * Splits a given text by [SYSTEM-LIKE INSTRUCTIONS] and [TASK] tags.
     *
     * @param string $text The input text containing the tags.
     * @return array{systemLikeInstructions: string, task: string, rest: string}
     *               An associative array with the parsed sections.
     *               Missing sections will be empty strings.
     */
    public static function splitByTags(string $text): array {
        $result = [
            'systemLikeInstructions' => '',
            'task' => '',
            'rest' => '',
        ];

        $systemTag = '[SYSTEM-LIKE INSTRUCTIONS]';
        $taskTag = '[TASK]';

        $systemPos = strpos($text, $systemTag);
        $taskPos = strpos($text, $taskTag);

        if ($systemPos === false && $taskPos === false) {
            $result['rest'] = trim($text);
            return $result;
        }

        if ($systemPos !== false && $taskPos !== false) {
            // Both tags are present — extract content between/after them
            if ($systemPos < $taskPos) {
                $afterSystem = $systemPos + strlen($systemTag);
                $result['systemLikeInstructions'] = trim(
                    substr($text, $afterSystem, $taskPos - $afterSystem)
                );
                $result['task'] = trim(
                    substr($text, $taskPos + strlen($taskTag))
                );
            } else {
                $afterTask = $taskPos + strlen($taskTag);
                $result['task'] = trim(
                    substr($text, $afterTask, $systemPos - $afterTask)
                );
                $result['systemLikeInstructions'] = trim(
                    substr($text, $systemPos + strlen($systemTag))
                );
            }

            // Anything before the first tag is considered "rest"
            $firstPos = min($systemPos, $taskPos);
            if ($firstPos > 0) {
                $result['rest'] = trim(substr($text, 0, $firstPos));
            }
        } elseif ($systemPos !== false) {
            // Only [SYSTEM-LIKE INSTRUCTIONS] is present
            $result['systemLikeInstructions'] = trim(
                substr($text, $systemPos + strlen($systemTag))
            );
            if ($systemPos > 0) {
                $result['rest'] = trim(substr($text, 0, $systemPos));
            }
        } else {
            // Only [TASK] is present
            $result['task'] = trim(
                substr($text, $taskPos + strlen($taskTag))
            );
            if ($taskPos > 0) {
                $result['rest'] = trim(substr($text, 0, $taskPos));
            }
        }

        return $result;
    }


    public function extractRequestParameters(string $prompt, array $userContent): array
    {
        $promptParts = self::splitByTags($prompt);
        $systemPrompt = $promptParts['systemLikeInstructions'];
        $userPrompt = $promptParts['task'] === '' ? $promptParts['rest'] : $promptParts['task'];
        $userContent[] = [
            "type" => "input_text",
            "text" => $userPrompt,
        ];

        $systemContent = [];
        $systemContent[] = [
            "type" => "input_text",
            "text" => $systemPrompt,
        ];
        global $wgOpenAIModel, $wgOpenAIModelReasoning;
        return [
            "model" => $wgOpenAIModel ?? "o3",
            "reasoning" => ["effort" => $wgOpenAIModelReasoning ?? "none"],
            "input" => [
                [
                    "role" => "user",
                    "content" => $userContent,
                ],
                [
                    "role" => "developer",
                    "content" => $systemContent,
                ]
            ],

        ];
    }
}
