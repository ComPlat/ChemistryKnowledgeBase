<?php

namespace DIQA\ChemExtension\Eval;

use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\Utils\LoggerUtils;

/**
 * Audits the GOLD standard itself against the source document. Manual curation has errors too;
 * when the optimization repeatedly disagrees with a gold value, that value may be the thing that
 * is wrong. This auditor asks the model to verify each recorded gold value against the paper and,
 * where it is wrong, propose the correct value with a verbatim supporting quote and a confidence.
 *
 * The output is only a set of *candidates* with evidence — it is never applied automatically
 * (that would let the model rewrite its own ground truth). A human confirms the findings, and
 * {@see \DIQA\ChemExtension\Eval\GoldCorrections} applies the confirmed subset. So prompt and gold
 * standard improve together, on evidence.
 */
class GoldAuditor
{
    private AIClient $client;
    private float $threshold;
    private LoggerUtils $logger;

    public function __construct(?AIClient $client = null, float $threshold = 0.7)
    {
        $this->client = $client ?? new AIClient();
        $this->threshold = $threshold;
        $this->logger = new LoggerUtils('GoldAuditor', 'ChemExtension');
    }

    /**
     * @param string[] $fileIds  already-uploaded document file ids
     * @param array<int, array<string,string>> $goldRows
     * @param string[] $imageFileIds
     * @return array<int, array{rowIndex:int, field:string, goldValue:string, suggestedValue:string, confidence:float, evidence:string}>
     */
    public function auditWithFiles(array $fileIds, array $goldRows, array $imageFileIds = []): array
    {
        if (empty($goldRows)) {
            return [];
        }
        $rowsJson = json_encode(array_values($goldRows), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $prompt = "[SYSTEM-LIKE INSTRUCTIONS]\n"
            . "The following values were recorded by a human from the attached publication "
            . "(JSON list, 0-based index per experiment). Audit each value against the document. "
            . "Only report a finding when a recorded value is WRONG or clearly mis-transcribed: give "
            . "the corrected value, a short verbatim quote from the document that proves it, and your "
            . "confidence in [0,1]. Do not report values you cannot verify, and do not nitpick "
            . "formatting. Molecule identifiers like 'Molecule:123' are internal references — never "
            . "flag those.\n"
            . "[TASK]\nRecorded values (JSON):\n" . $rowsJson;

        $raw = $this->client->callAIWithSchema($fileIds, $prompt, self::schema(), 'gold_audit', $imageFileIds);
        $this->logger->log("Gold audit response: " . $raw);
        return self::parseFindings($raw, $goldRows, $this->threshold);
    }

    public static function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'findings' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'index' => ['type' => 'integer'],
                            'field' => ['type' => 'string'],
                            'gold_value' => ['type' => ['string', 'null']],
                            'correct_value' => ['type' => ['string', 'null']],
                            'confidence' => ['type' => 'number'],
                            'evidence' => ['type' => ['string', 'null']],
                        ],
                        'required' => ['index', 'field', 'gold_value', 'correct_value', 'confidence', 'evidence'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['findings'],
            'additionalProperties' => false,
        ];
    }

    /**
     * Keeps only findings that (a) refer to a real gold cell, (b) actually change the value, and
     * (c) meet the confidence threshold.
     *
     * @param array<int, array<string,string>> $goldRows
     * @return array<int, array{rowIndex:int, field:string, goldValue:string, suggestedValue:string, confidence:float, evidence:string}>
     */
    public static function parseFindings(string $json, array $goldRows, float $threshold = 0.7): array
    {
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['findings']) || !is_array($data['findings'])) {
            return [];
        }
        $out = [];
        foreach ($data['findings'] as $f) {
            if (!isset($f['index'], $f['field'])) {
                continue;
            }
            $idx = (int) $f['index'];
            $field = (string) $f['field'];
            $suggested = isset($f['correct_value']) ? (string) $f['correct_value'] : '';
            $confidence = max(0.0, min(1.0, (float) ($f['confidence'] ?? 0.0)));
            if (!isset($goldRows[$idx][$field])) {
                continue; // not a real gold cell
            }
            $goldValue = (string) $goldRows[$idx][$field];
            if ($suggested === '' || self::norm($suggested) === self::norm($goldValue)) {
                continue; // no actual change proposed
            }
            if (str_starts_with($goldValue, 'Molecule:')) {
                continue; // never flag internal molecule references
            }
            if ($confidence < $threshold) {
                continue;
            }
            $out[] = [
                'rowIndex' => $idx,
                'field' => $field,
                'goldValue' => $goldValue,
                'suggestedValue' => $suggested,
                'confidence' => $confidence,
                'evidence' => isset($f['evidence']) ? (string) $f['evidence'] : '',
            ];
        }
        // most confident first
        usort($out, fn($a, $b) => $b['confidence'] <=> $a['confidence']);
        return $out;
    }

    private static function norm(string $v): string
    {
        return preg_replace('/\s+/', ' ', trim(mb_strtolower($v)));
    }
}
