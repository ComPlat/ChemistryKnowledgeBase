<?php

namespace DIQA\ChemExtension\PublicationImport;

use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;
use OpenAI;

class AIClient
{

    private $client;
    private $logger;

    public function __construct()
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


    public function uploadFiles(array $files)
    {
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

    public function uploadTextAsFile($text)
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

    public function deleteFiles(array $files)
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

    public function callAI(array $fileIds, string $prompt)
    {
        $this->logger->log("Request to AI with prompt: '$prompt' and documents [" . join($fileIds) . "]");
        $userContent = array_map(fn($fileId) => ["type" => "input_file", "file_id" => $fileId], $fileIds);
        $parameters = $this->extractRequestParameters($prompt, $userContent);
        $response = $this->client->responses()->create($parameters);
        $result = $response->outputText ?? 'no output generated';
        $this->logger->log("Response from AI: " . $result);
        return $result;
    }

    public function callAIWithTextInputs(array $textInputs, string $prompt)
    {
        $this->logger->log("Request to AI with prompt: '$prompt' and documents [" . join($textInputs) . "]");
        $userContent = array_map(fn($text) => ["type" => "input_text", "text" => $text], $textInputs);
        $parameters = $this->extractRequestParameters($prompt, $userContent);
        $response = $this->client->responses()->create($parameters);
        $result = $response->outputText ?? 'no output generated';
        $this->logger->log("Response from AI: " . $result);
        return $result;
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

    /**
     * @param string $prompt
     * @param array $userContent
     * @return array
     */
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
