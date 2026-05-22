<?php

namespace DIQA\ChemExtension\Eval;

/**
 * Secondary metric: semantic similarity between the AI-written prose and the human-written
 * reference prose, via embeddings.
 *
 * This complements the structured field metric ({@see ExtractionScorer}). It is deliberately a
 * support signal with low weight — text similarity rewards verbosity and paraphrase, so it must
 * not dominate the structured correctness score.
 *
 * The gold reference prose comes from the "prose" field of a gold entry; it may be either a
 * single string or a map of section name => text (then sections are compared individually and
 * averaged).
 */
class ProseSimilarityScorer
{
    private const CSV_BLOCK = '/(<pre>|```csv)(.*?)(<\/pre>|```)/s';

    private EmbeddingClient $embeddingClient;

    public function __construct(EmbeddingClient $embeddingClient)
    {
        $this->embeddingClient = $embeddingClient;
    }

    /**
     * @param string $aiResponse the full AI response (CSV block is stripped out)
     * @param string|array<string,string> $goldProse reference prose (string or section map)
     * @return float cosine similarity in [0, 1] (negative values clamped to 0)
     */
    public function score(string $aiResponse, $goldProse): float
    {
        $aiProse = self::stripCsvBlocks($aiResponse);
        if (trim($aiProse) === '') {
            return 0.0;
        }
        // embed the AI prose once, then compare against the reference (section-wise if a map)
        $aiVec = $this->embeddingClient->embed($aiProse);
        if (empty($aiVec)) {
            return 0.0;
        }

        if (is_array($goldProse)) {
            $sims = [];
            foreach ($goldProse as $sectionText) {
                if (trim((string) $sectionText) === '') {
                    continue;
                }
                $sims[] = $this->cosineTo($aiVec, (string) $sectionText);
            }
            if (empty($sims)) {
                return 0.0;
            }
            return array_sum($sims) / count($sims);
        }

        return $this->cosineTo($aiVec, (string) $goldProse);
    }

    private function cosineTo(array $aiVec, string $reference): float
    {
        $refVec = $this->embeddingClient->embed($reference);
        return max(0.0, EmbeddingClient::cosineSimilarity($aiVec, $refVec));
    }

    public static function stripCsvBlocks(string $text): string
    {
        return trim(preg_replace(self::CSV_BLOCK, '', $text));
    }
}
