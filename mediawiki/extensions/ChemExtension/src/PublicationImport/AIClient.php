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
                    $this->logger->error("Could not upload file to OpenAI: $file\n". print_r($response, true));
                    continue;
                }
                $ids[] = $response->id;
                $this->logger->log(sprintf("Uploaded file: %s, id: %s", $response->filename, $response->id));
            } catch(Exception $e) {
                $this->logger->error("Could not upload file to OpenAI: $file");
                $this->logger->error($e->getMessage());
            }
        }
        return $ids;
    }

    public function deleteFiles(array $files) {
        foreach($files as $fileId) {
            $response = $this->client->files()->delete($fileId);
            if (!$response->deleted) {
                $this->logger->warn("File could not be deleted in OpenAI repo: $fileId");
            } else {
                $this->logger->log("File deleted from OpenAI repo: $fileId");
            }
        }
    }

    public function callAI(array $fileIds, string $prompt) {
        $this->logger->log("Request to AI with prompt: '$prompt' and documents [" . join($fileIds) . "]");
        $content = array_map(fn($fileId) => ["type" => "input_file", "file_id" => $fileId], $fileIds);
        $content[] = [
            "type" => "input_text",
            "text" => $prompt,
        ];

        global $wgOpenAIModel;
        $response = $this->client->responses()->create([
                "model" => $wgOpenAIModel ?? "o3",
                "input" => [
                    [
                        "role" => "user",
                        "content" => $content,
                    ]
                ],

            ]
        );
        $result = $response->outputText ?? 'no output generated';
        $this->logger->log("Response from AI: " . $result);
        return $result;
    }
}
