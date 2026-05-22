<?php

namespace DIQA\ChemExtension\Eval;

/**
 * Persistent memory for the self-optimizing extraction loop.
 *
 * Per topic it keeps:
 *   - runs/<timestamp>.json : the full record of one iteration (prompt, aggregate metric,
 *     per-field recall, example errors),
 *   - memory.md             : a human-readable, append-only log the optimizer reads back as
 *     context so it "remembers" earlier iterations and learns from past mistakes.
 *
 * This lets the loop survive across invocations and gives the team an auditable trail of how
 * each prompt variant scored — the evidence base for the paper.
 */
class EvalMemory
{
    private string $topicDir;
    private string $runsDir;
    private string $memoryFile;

    public function __construct(string $topicDir)
    {
        $this->topicDir = $topicDir;
        $this->runsDir = $topicDir . '/runs';
        $this->memoryFile = $topicDir . '/memory.md';
        if (!is_dir($this->runsDir)) {
            mkdir($this->runsDir, 0775, true);
        }
    }

    /**
     * @param array $run {iteration:int, prompt:string, metric:array, timestamp:string}
     */
    public function recordRun(array $run): void
    {
        $ts = $run['timestamp'] ?? date('Ymd_His');
        file_put_contents(
            $this->runsDir . "/{$ts}.json",
            json_encode($run, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
        $this->appendToMemory($run);
    }

    /**
     * Returns the markdown memory log so the optimizer can use it as context.
     */
    public function getMemoryText(): string
    {
        return is_file($this->memoryFile) ? file_get_contents($this->memoryFile) : '';
    }

    /**
     * Best F1 recorded so far and the prompt that achieved it, or null.
     *
     * @return array{f1:float, prompt:string}|null
     */
    public function getBest(): ?array
    {
        $best = null;
        foreach (glob($this->runsDir . '/*.json') as $file) {
            $run = json_decode(file_get_contents($file), true);
            $f1 = $run['metric']['f1'] ?? null;
            if ($f1 === null) {
                continue;
            }
            if ($best === null || $f1 > $best['f1']) {
                $best = ['f1' => $f1, 'prompt' => $run['prompt'] ?? ''];
            }
        }
        return $best;
    }

    private function appendToMemory(array $run): void
    {
        $metric = $run['metric'] ?? [];
        $f1 = number_format($metric['f1'] ?? 0, 4);
        $precision = number_format($metric['precision'] ?? 0, 4);
        $recall = number_format($metric['recall'] ?? 0, 4);
        $iteration = $run['iteration'] ?? '?';
        $ts = $run['timestamp'] ?? date('Ymd_His');

        $worstFields = [];
        foreach (($metric['perField'] ?? []) as $field => $counts) {
            $r = number_format($counts['recall'] ?? 0, 2);
            $worstFields[] = "$field (recall $r, {$counts['correct']}/{$counts['gold']})";
            if (count($worstFields) >= 8) {
                break;
            }
        }

        $entry = "## Iteration $iteration — $ts\n"
            . "- F1: $f1 | Precision: $precision | Recall: $recall\n"
            . "- Weakest fields: " . (empty($worstFields) ? '—' : implode('; ', $worstFields)) . "\n\n";

        file_put_contents($this->memoryFile, $entry, FILE_APPEND);
    }
}
