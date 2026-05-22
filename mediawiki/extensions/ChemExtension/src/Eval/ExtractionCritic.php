<?php

namespace DIQA\ChemExtension\Eval;

use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\Utils\LoggerUtils;

/**
 * Second-pass verifier ("critic"): given the source document(s) and the extracted experiment
 * rows, it asks the model to confirm each row against the text and return a confidence in [0,1]
 * plus a short supporting quote.
 *
 * This turns a silent extraction into a checkable one: rows below a confidence threshold can be
 * routed to human review instead of being published, and the average confidence is a quality
 * signal for the optimization loop. Provenance (the evidence quote) makes the result auditable —
 * directly supporting the "prove the accuracy" goal.
 */
class ExtractionCritic
{
    private AIClient $client;
    private float $threshold;
    private LoggerUtils $logger;

    public function __construct(?AIClient $client = null, float $threshold = 0.6)
    {
        $this->client = $client ?? new AIClient();
        $this->threshold = $threshold;
        $this->logger = new LoggerUtils('ExtractionCritic', 'ChemExtension');
    }

    /**
     * Reviews extracted rows against already-uploaded document file ids.
     *
     * @param string[] $fileIds      uploaded document file ids (reused, not re-uploaded)
     * @param array<int, array<string,string>> $rows
     * @param string[] $imageFileIds optional vision image file ids
     * @return array{avgConfidence:?float, rowConfidences:float[], flagged:int[], evidence:array<int,string>}
     */
    public function reviewWithFiles(array $fileIds, array $rows, array $imageFileIds = []): array
    {
        if (empty($rows)) {
            return ['avgConfidence' => null, 'rowConfidences' => [], 'flagged' => [], 'evidence' => []];
        }

        $rowsJson = json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $prompt = "[SYSTEM-LIKE INSTRUCTIONS]\n"
            . "You are verifying data that was extracted from the attached publication. For each "
            . "experiment (by its 0-based index in the list), check every value against the document "
            . "and return a confidence in [0,1] that the row is correct and complete, plus a short "
            . "verbatim quote from the document supporting it (empty if you cannot find support).\n"
            . "[TASK]\nExtracted experiments (JSON):\n" . $rowsJson;

        $raw = $this->client->callAIWithSchema($fileIds, $prompt, self::schema(), 'critic', $imageFileIds);
        $this->logger->log("Critic response: " . $raw);

        return self::parseReview($raw, count($rows), $this->threshold);
    }

    public static function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'reviews' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'index' => ['type' => 'integer'],
                            'confidence' => ['type' => 'number'],
                            'evidence' => ['type' => ['string', 'null']],
                        ],
                        'required' => ['index', 'confidence', 'evidence'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['reviews'],
            'additionalProperties' => false,
        ];
    }

    /**
     * Parses the critic JSON into per-row confidences. Rows the critic did not return are treated
     * as unverified (confidence 0), so silence counts against the score.
     *
     * @return array{avgConfidence:?float, rowConfidences:float[], flagged:int[], evidence:array<int,string>}
     */
    public static function parseReview(string $json, int $rowCount, float $threshold = 0.6): array
    {
        $data = json_decode($json, true);
        $byIndex = [];
        $evidence = [];
        if (is_array($data) && isset($data['reviews']) && is_array($data['reviews'])) {
            foreach ($data['reviews'] as $review) {
                if (!isset($review['index'])) {
                    continue;
                }
                $idx = (int) $review['index'];
                $byIndex[$idx] = self::clamp((float) ($review['confidence'] ?? 0.0));
                if (!empty($review['evidence'])) {
                    $evidence[$idx] = (string) $review['evidence'];
                }
            }
        }

        $rowConfidences = [];
        $flagged = [];
        for ($i = 0; $i < $rowCount; $i++) {
            $c = $byIndex[$i] ?? 0.0;
            $rowConfidences[$i] = $c;
            if ($c < $threshold) {
                $flagged[] = $i;
            }
        }
        $avg = $rowCount > 0 ? array_sum($rowConfidences) / $rowCount : null;

        return [
            'avgConfidence' => $avg,
            'rowConfidences' => $rowConfidences,
            'flagged' => $flagged,
            'evidence' => $evidence,
        ];
    }

    private static function clamp(float $v): float
    {
        return max(0.0, min(1.0, $v));
    }
}
