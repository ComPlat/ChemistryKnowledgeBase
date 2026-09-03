<?php

namespace DIQA\ChemExtension\PublicationImport;

interface AIClientInterface
{
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
    public function ping(): array;

    public function uploadFiles(array $files): array;

    public function uploadTextAsFile($text): array;

    public function deleteFiles(array $files): void;

    public function callAI(array $fileIds, string $prompt): string;

    public function callAIWithTextInputs(array $textInputs, string $prompt): string;

    public function callAIWithSchema(array $fileIds, string $prompt, array $jsonSchema, string $schemaName = 'extraction', array $imageFileIds = []): string;

    public function buildFileContent(array $fileIds, array $imageFileIds = []): array;

    public function extractRequestParameters(string $prompt, array $userContent): array;

    public function parallelPost(array $requestBody, int $n): array;


}