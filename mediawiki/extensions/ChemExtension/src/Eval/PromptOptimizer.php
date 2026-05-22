<?php

namespace DIQA\ChemExtension\Eval;

use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\Utils\LoggerUtils;

/**
 * Proposes an improved extraction prompt from the current prompt and the latest error report.
 *
 * Uses the LLM itself as a meta-optimizer: it is shown the current prompt, the aggregate metric,
 * the systematically weak fields and concrete mismatch examples, plus the running memory of past
 * iterations, and is asked to rewrite the prompt so the next extraction scores higher — while
 * keeping the hard contract intact (exact column headers, fenced ```csv output).
 */
class PromptOptimizer
{
    private AIClient $aiClient;
    private LoggerUtils $logger;

    public function __construct(?AIClient $aiClient = null)
    {
        $this->aiClient = $aiClient ?? new AIClient();
        $this->logger = new LoggerUtils('PromptOptimizer', 'ChemExtension');
    }

    /**
     * @param string $currentPrompt
     * @param array  $aggregateMetric result of ExtractionScorer::aggregate()
     * @param string $memoryText      EvalMemory::getMemoryText()
     * @return string the proposed improved prompt
     */
    public function proposeImprovedPrompt(string $currentPrompt, array $aggregateMetric, string $memoryText): string
    {
        $weakFields = [];
        foreach (($aggregateMetric['perField'] ?? []) as $field => $counts) {
            $recall = number_format($counts['recall'] ?? 0, 2);
            $weakFields[] = "- $field: recall $recall ({$counts['correct']}/{$counts['gold']} correct)";
            if (count($weakFields) >= 15) {
                break;
            }
        }
        $examples = array_slice($aggregateMetric['examples'] ?? [], 0, 25);

        $f1 = number_format($aggregateMetric['f1'] ?? 0, 4);
        $precision = number_format($aggregateMetric['precision'] ?? 0, 4);
        $recall = number_format($aggregateMetric['recall'] ?? 0, 4);

        $weakFieldsText = empty($weakFields) ? '(none)' : implode("\n", $weakFields);
        $examplesText = empty($examples) ? '(none)' : "- " . implode("\n- ", $examples);
        $memorySection = trim($memoryText) === '' ? '(no earlier iterations)' : $memoryText;

        $systemInstructions = <<<SYS
You are optimizing the instruction prompt used to extract structured experimental data from
chemistry publications into a CSV table. You will be given the current prompt, how well it scored
against a curated gold set, which fields it gets wrong most often, concrete mismatch examples, and
a memory log of previous attempts.

Rewrite the prompt so the next extraction reproduces the gold values more accurately. Hard rules
you MUST keep:
- Keep the exact CSV column headers (names and order) from the current prompt; do not add, drop or
  rename columns.
- Keep the requirement that the experiment table is emitted as a fenced code block delimited by
  ```csv and ```.
- Keep the overall section structure of the summary.
Improve only the wording, the guidance per field, disambiguation rules, and edge-case handling
(units, scientific notation, per-product values, empty cells, one row per experiment, etc.).

Respond with the FULL improved prompt text only. No commentary, no code fences around the whole
answer.
SYS;

        $task = <<<TASK
[CURRENT METRIC]
F1: $f1 | Precision: $precision | Recall: $recall

[SYSTEMATICALLY WEAK FIELDS]
$weakFieldsText

[MISMATCH EXAMPLES]
$examplesText

[MEMORY OF PREVIOUS ITERATIONS]
$memorySection

[CURRENT PROMPT]
$currentPrompt
TASK;

        $metaPrompt = "[SYSTEM-LIKE INSTRUCTIONS]\n$systemInstructions\n\n[TASK]\n$task";

        $this->logger->log("Requesting improved prompt from optimizer (current F1=$f1)");
        $improved = $this->aiClient->callAIWithTextInputs([], $metaPrompt);
        $improved = $this->stripWrappingFence(trim($improved));

        if ($improved === '') {
            $this->logger->warn("Optimizer returned empty prompt — keeping current prompt");
            return $currentPrompt;
        }
        return $improved;
    }

    /**
     * Removes a single ``` ... ``` fence if the model wrapped the whole answer in one.
     */
    private function stripWrappingFence(string $text): string
    {
        if (preg_match('/^```[a-zA-Z]*\s*\n(.*)\n```$/s', $text, $m)) {
            return trim($m[1]);
        }
        return $text;
    }
}
