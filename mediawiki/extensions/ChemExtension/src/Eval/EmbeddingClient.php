<?php

namespace DIQA\ChemExtension\Eval;

use DIQA\ChemExtension\PublicationImport\AIProviderConfig;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;
use OpenAI;

/**
 * Thin wrapper around the OpenAI-compatible embeddings endpoint of the configured AI provider,
 * used by {@see ProseSimilarityScorer} to compare AI-written prose against the human-written
 * reference text semantically.
 *
 * Model: $wgCEAIEmbeddingModel (legacy: $wgOpenAIEmbeddingModel), otherwise the provider
 * default (OpenAI: text-embedding-3-small, KIT: kit.qwen3-embedding-8b). Self-hosted servers
 * need the model configured explicitly.
 */
class EmbeddingClient
{
    private const DEFAULT_MODELS = [
        AIProviderConfig::OPENAI => 'text-embedding-3-small',
        AIProviderConfig::KIT => 'kit.qwen3-embedding-8b',
    ];

    private $client;
    private $logger;
    private string $model;

    public function __construct(?AIProviderConfig $config = null)
    {
        $config = $config ?? AIProviderConfig::fromGlobals();
        if ($config->requiresApiKey() && $config->getApiKey() === null) {
            throw new Exception("API key for AI provider '{$config->getProvider()}' is missing (needed for embeddings).");
        }
        $model = $GLOBALS['wgCEAIEmbeddingModel'] ?? $GLOBALS['wgOpenAIEmbeddingModel']
            ?? self::DEFAULT_MODELS[$config->getProvider()] ?? null;
        if ($model === null) {
            throw new Exception("No embedding model for AI provider '{$config->getProvider()}'. Set \$wgCEAIEmbeddingModel "
                . "or run with --no-embeddings.");
        }
        $this->logger = new LoggerUtils('EmbeddingClient', 'ChemExtension');
        $this->model = $model;
        $this->client = OpenAI::factory()
            ->withApiKey($config->getApiKey() ?? '')
            ->withBaseUri($config->getBaseUrl())
            ->make();
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
