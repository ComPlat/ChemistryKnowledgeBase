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
 * Parallel by design: the N calls fire concurrently via
 * {@see AIClientInterface::callAIParallel()}, so wall-time is ~1× a single call (not N×).
 * Works with every provider (OpenAI, KIT KI-Toolbox, self-hosted models).
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

        // All N passes use identical input (same PDFs, same prompt); the variation comes
        // from the model's stochastic decoding on the server side.
        $responses = $this->aiClient->callAIParallel($fileIds, $prompt, $n, $imageFileIds);

        // Failed passes are skipped; if ALL fail, there is nothing to merge.
        $passes = [];
        $ok = 0;
        foreach ($responses as $i => $resp) {
            if ($resp['text'] !== '') {
                $ok++;
                $passes[] = $resp['text'];
            } else {
                $this->logger->warn("EnsembleExtractor: pass " . ($i + 1) . " failed: " . ($resp['error'] ?? 'empty output'));
            }
        }
        $this->logger->log("EnsembleExtractor: $ok/$n passes succeeded, merging");

        if (empty($passes)) {
            throw new Exception("EnsembleExtractor: all $n parallel passes failed on "
                                . $this->aiClient->describe() . " — check API key, credits, rate limits");
        }

        $merged = ExtractionMerger::merge($passes);
        $this->logger->log("EnsembleExtractor: merged " . count($passes) . " passes into "
                           . strlen($merged) . " chars of canonical wikitext");
        return $merged;
    }

}
