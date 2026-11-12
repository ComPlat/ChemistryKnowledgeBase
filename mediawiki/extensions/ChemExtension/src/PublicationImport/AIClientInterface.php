<?php

namespace DIQA\ChemExtension\PublicationImport;

/**
 * Provider-independent LLM client used by the publication import, tagging, spam detection and
 * the extraction evaluation loop. Obtain an instance via {@see AIClientFactory::create()}.
 *
 * "File ids" are opaque handles returned by {@see uploadFiles()}. Depending on the provider
 * they refer to remotely stored files (OpenAI Files API) or to local files that are inlined
 * into each request (Chat Completions providers). Callers must release them with
 * {@see deleteFiles()} in either case.
 */
interface AIClientInterface
{
    /**
     * Checks whether the backend is operational and requests are accepted
     * (e.g. not refused due to an empty budget, invalid key, exceeded quota, etc.).
     *
     * Performs a minimal, low-cost request and inspects the response. On any
     * failure, the underlying error is logged and ok=false is returned.
     *
     * @return array{ok: bool, message: string} Status info: ok=true on success,
     *                                          message contains details on failure.
     */
    public function ping(): array;

    /**
     * Human-readable provider/model identifier, e.g. "kit / azure.gpt-5.4".
     */
    public function describe(): string;

    /**
     * @param string[] $files local paths (PDF, text or image files)
     * @return string[] file ids; files that could not be registered are skipped and logged
     */
    public function uploadFiles(array $files): array;

    /**
     * @return string[] a single file id holding the given text
     */
    public function uploadTextAsFile($text): array;

    /**
     * @param string[] $files file ids from uploadFiles()/uploadTextAsFile()
     */
    public function deleteFiles(array $files): void;

    /**
     * @param string[] $fileIds      document file ids
     * @param string[] $imageFileIds image file ids (e.g. rendered PDF pages) for vision input
     */
    public function callAI(array $fileIds, string $prompt, array $imageFileIds = []): string;

    /**
     * Runs the same request $n times concurrently (ensemble extraction). Wall-time is roughly
     * that of a single call.
     *
     * @param string[] $fileIds
     * @param string[] $imageFileIds
     * @return array<int, array{text: string, error: ?string}> one entry per pass, in order;
     *         failed passes have text '' and an error message
     */
    public function callAIParallel(array $fileIds, string $prompt, int $n, array $imageFileIds = []): array;

    /**
     * @param string[] $textInputs plain-text documents sent alongside the prompt
     */
    public function callAIWithTextInputs(array $textInputs, string $prompt): string;

    /**
     * Like callAI(), but constrains the model to a JSON schema (structured outputs) and
     * returns the raw JSON string.
     *
     * @param array $jsonSchema a JSON-schema object (see Eval\TopicSchema)
     */
    public function callAIWithSchema(array $fileIds, string $prompt, array $jsonSchema, string $schemaName = 'extraction', array $imageFileIds = []): string;

    /**
     * Token usage of the most recent single (non-parallel) request.
     *
     * @return array{input:int, output:int, total:int}|null
     */
    public function getLastUsage(): ?array;
}
