<?php

namespace DIQA\ChemExtension\PublicationImport;

class AIClientMock implements AIClientInterface {

    public function ping(): array
    {
        return ['ok' => true, 'message' => 'pong'];
    }

    public function describe(): string
    {
        return 'mock';
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

    public function callAI(array $fileIds, string $prompt, array $imageFileIds = []): string
    {
        return "callAI response";
    }

    public function callAIParallel(array $fileIds, string $prompt, int $n, array $imageFileIds = []): array
    {
        return array_fill(0, $n, ['text' => 'callAIParallel response', 'error' => null]);
    }

    public function callAIWithTextInputs(array $textInputs, string $prompt): string
    {
        return "callAIWithTextInputs response";
    }

    public function callAIWithSchema(array $fileIds, string $prompt, array $jsonSchema, string $schemaName = 'extraction', array $imageFileIds = []): string
    {
        return "callAIWithSchema response";
    }

    public function getLastUsage(): ?array
    {
        return null;
    }
}
