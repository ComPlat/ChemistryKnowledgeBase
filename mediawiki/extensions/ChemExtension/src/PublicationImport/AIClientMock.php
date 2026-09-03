<?php

namespace DIQA\ChemExtension\PublicationImport;

class AIClientMock implements AIClientInterface {

    public function ping(): array
    {
        return ['ok' => true, 'message' => 'pong'];
    }

    public function uploadFiles(array $files): array
    {
        return [];
    }

    public function uploadTextAsFile($text): array
    {
        return [];
    }

    public function deleteFiles(array $files): void
    {
        // empty
    }

    public function callAI(array $fileIds, string $prompt): string
    {
        return "callAI response";
    }

    public function callAIWithTextInputs(array $textInputs, string $prompt): string
    {
        return "callAIWithTextInputs response";
    }

    public function callAIWithSchema(array $fileIds, string $prompt, array $jsonSchema, string $schemaName = 'extraction', array $imageFileIds = []): string
    {
        return "callAIWithSchema response";
    }

    public function buildFileContent(array $fileIds, array $imageFileIds = []): array
    {
        return [];
    }

    public function extractRequestParameters(string $prompt, array $userContent): array
    {
        return [];
    }

    public function parallelPost(array $requestBody, int $n): array
    {
        return [ 'first' => ['output_text' => 'parallelPost response'] ];
    }
}