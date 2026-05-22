<?php

namespace DIQA\ChemExtension\Eval;

use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;
use OpenAI;

/**
 * Thin wrapper around the OpenAI embeddings endpoint, used by {@see ProseSimilarityScorer}
 * to compare AI-written prose against the human-written reference text semantically.
 *
 * Configure the model via $wgOpenAIEmbeddingModel (default text-embedding-3-small).
 * Requires $wgOpenAIKey, like {@see \DIQA\ChemExtension\PublicationImport\AIClient}.
 */
class EmbeddingClient
{
    private $client;
    private $logger;
    private string $model;

    public function __construct()
    {
        global $wgOpenAIKey, $wgOpenAIEmbeddingModel;
        if (!isset($wgOpenAIKey)) {
            throw new Exception('OpenAI-Key is missing. please configure $wgOpenAIKey');
        }
        $this->logger = new LoggerUtils('EmbeddingClient', 'ChemExtension');
        $this->model = $wgOpenAIEmbeddingModel ?? 'text-embedding-3-small';
        $this->client = OpenAI::factory()->withApiKey($wgOpenAIKey)->make();
    }

    /**
     * @param string $text
     * @return float[] the embedding vector
     * @throws Exception
     */
    public function embed(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
        $response = $this->client->embeddings()->create([
            'model' => $this->model,
            'input' => $text,
        ]);
        if (empty($response->embeddings)) {
            $this->logger->warn('Embedding response was empty');
            return [];
        }
        return $response->embeddings[0]->embedding;
    }

    /**
     * Cosine similarity of two vectors, in [-1, 1]; 0 if either is empty.
     *
     * @param float[] $a
     * @param float[] $b
     */
    public static function cosineSimilarity(array $a, array $b): float
    {
        $n = min(count($a), count($b));
        if ($n === 0) {
            return 0.0;
        }
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }
        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }
        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
