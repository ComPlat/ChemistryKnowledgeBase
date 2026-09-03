<?php

namespace DIQA\ChemExtension\PublicationImport;

use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

/**
 * N-pass ensemble wrapper around AIClient.
 *
 * Problem it solves: o3 is not deterministic across calls even at temperature 0. Re-importing
 * the same PDF with the same prompt yields subtly different extractions — most notably, the
 * ROW SET fluctuates (mal 5, mal 8 experiment rows out of the same table). Cell values inside
 * a stable row are more consistent.
 *
 * Fix: run N parallel extraction requests via curl_multi (all with the same prompt +
 * uploaded PDFs), then merge deterministically (row-union + cell-majority) via
 * {@see ExtractionMerger}. Same PDF/prompt → same wiki page, regardless of retries.
 *
 * Parallel by design: the N calls fire concurrently as HTTP POSTs to the OpenAI Responses
 * endpoint, so wall-time is ~1× a single call (not N×). Bypasses the OpenAI PHP SDK for the
 * parallel path since the SDK doesn't expose an async API; single-pass path stays on the SDK
 * for full retry/backoff behaviour and zero behaviour change.
 *
 * Backwards-compatible: N=1 short-circuits to a single AIClient::callAI call. Enable via
 * `$wgAIExtractionPasses = 3;` in LocalSettings.php.
 */
class EnsembleExtractor
{


    private AIClientInterface $aiClient;
    private LoggerUtils $logger;

    public function __construct(AIClientInterface $aiClient)
    {
        $this->aiClient = $aiClient;
        $this->logger = new LoggerUtils('EnsembleExtractor', 'ChemExtension');
    }

    /**
     * Extract with N parallel passes and merged deterministic output.
     *
     * @param string[] $fileIds       Uploaded PDF file ids from AIClient::uploadFiles
     * @param string   $prompt        Extraction prompt (resolved by the caller, e.g. topic-specific)
     * @param int|null $numPasses     Override for $wgAIExtractionPasses; null = read from config
     * @param string[] $imageFileIds  Optional pre-rendered PDF page images (passed through)
     * @return string Merged wikitext (prose + one fenced csv block), byte-identical shape to
     *                 a single callAI response.
     */
    public function extract(array $fileIds, string $prompt, ?int $numPasses = null,
                             array $imageFileIds = []): string
    {
        $n = $numPasses ?? (int)($GLOBALS['wgAIExtractionPasses'] ?? 1);
        if ($n <= 1) {
            // Legacy path: single call via the SDK (keeps its retry/backoff behaviour).
            // Zero behaviour change vs before the ensemble was introduced.
            return $this->aiClient->callAI($fileIds, $prompt, $imageFileIds);
        }
        $this->logger->log("EnsembleExtractor: firing $n parallel extraction passes");

        // Build ONE request body — all N passes use identical input (same PDFs, same prompt).
        // The variation comes from o3's stochastic decoding on the server side.
        $userContent = $this->aiClient->buildFileContent($fileIds, $imageFileIds);
        $requestBody = $this->aiClient->extractRequestParameters($prompt, $userContent);

        $responses = $this->aiClient->parallelPost($requestBody, $n);

        // Extract output_text from each response. Failed passes contribute '' so the merger
        // simply sees them as empty inputs and skips them; if ALL fail, merge returns ''.
        $passes = [];
        $ok = 0;
        foreach ($responses as $i => $resp) {
            $text = self::extractOutputText($resp);
            if ($text !== '') {
                $ok++;
                $passes[] = $text;
            } else {
                $err = $resp['_error'] ?? 'empty output';
                $this->logger->warn("EnsembleExtractor: pass " . ($i + 1) . " failed: $err");
            }
        }
        $this->logger->log("EnsembleExtractor: $ok/$n passes succeeded, merging");

        if (empty($passes)) {
            throw new Exception("EnsembleExtractor: all $n parallel passes failed — check API key, "
                                . "credits, rate limits");
        }

        $merged = ExtractionMerger::merge($passes);
        $this->logger->log("EnsembleExtractor: merged " . count($passes) . " passes into "
                           . strlen($merged) . " chars of canonical wikitext");
        return $merged;
    }



    /**
     * Pull the extracted text out of an OpenAI Responses API JSON response. The API returns
     * a top-level `output_text` in some shapes and a nested `output[*].content[*].text` in
     * others — handle both robustly, return '' when neither is present.
     */
    private static function extractOutputText(array $response): string
    {
        if (isset($response['output_text']) && is_string($response['output_text'])) {
            return $response['output_text'];
        }
        if (isset($response['output']) && is_array($response['output'])) {
            foreach ($response['output'] as $item) {
                if (!isset($item['content']) || !is_array($item['content'])) {
                    continue;
                }
                foreach ($item['content'] as $c) {
                    if (($c['type'] ?? '') === 'output_text' && isset($c['text']) && is_string($c['text'])) {
                        return $c['text'];
                    }
                }
            }
        }
        return '';
    }
}
