<?php

namespace DIQA\ChemExtension\Eval;

use DIQA\ChemExtension\PublicationImport\AIClient;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

/**
 * Orchestrates the self-optimizing extraction loop for one topic.
 *
 * Each iteration:
 *   1. run the current prompt against every gold publication's PDF (AIClient),
 *   2. parse the CSV table out of the response (CsvExtractionParser),
 *   3. score it field-by-field against the gold experiments (ExtractionScorer),
 *   4. record the run + aggregate metric to persistent memory (EvalMemory),
 *   5. ask the optimizer for an improved prompt (PromptOptimizer) and repeat.
 *
 * The loop converges towards the metric; the best prompt across all iterations is returned and
 * can then be written to MediaWiki:Prompt_import_<Topic> (closing the loop with Teil B).
 *
 * Requires $wgOpenAIKey at runtime (used by AIClient). The PHP code itself is independent of the
 * key and the gold set, both of which are supplied by the operator.
 */
class EvalLoopRunner
{
    private GoldSetRepository $goldRepo;
    private ExtractionScorer $scorer;
    private AIClient $aiClient;
    private PromptOptimizer $optimizer;
    private LoggerUtils $logger;
    /** @var callable */
    private $progress;

    public function __construct(
        ?GoldSetRepository $goldRepo = null,
        ?ExtractionScorer $scorer = null,
        ?AIClient $aiClient = null,
        ?PromptOptimizer $optimizer = null,
        ?callable $progress = null
    ) {
        $this->goldRepo = $goldRepo ?? new GoldSetRepository();
        $this->scorer = $scorer ?? new ExtractionScorer();
        $this->aiClient = $aiClient ?? new AIClient();
        $this->optimizer = $optimizer ?? new PromptOptimizer($this->aiClient);
        $this->logger = new LoggerUtils('EvalLoopRunner', 'ChemExtension');
        $this->progress = $progress ?? function ($msg) {};
    }

    /**
     * Runs the optimization loop for a topic.
     *
     * @param string $topic         topic directory name (underscores)
     * @param string $initialPrompt the prompt to start from
     * @param int    $iterations    number of iterations (>=1)
     * @return array{best:array{f1:float,prompt:string}, history:array<int,array>}
     * @throws Exception
     */
    public function run(string $topic, string $initialPrompt, int $iterations = 5): array
    {
        $goldEntries = $this->goldRepo->loadTopic($topic);
        if (empty($goldEntries)) {
            throw new Exception("Gold set for topic '$topic' is empty.");
        }
        $memory = new EvalMemory($this->goldRepo->getTopicDir($topic));

        $prompt = $initialPrompt;
        $best = ['f1' => -1.0, 'prompt' => $initialPrompt];
        $history = [];

        for ($i = 1; $i <= $iterations; $i++) {
            $this->emit("\n=== Iteration $i/$iterations (topic: $topic) ===");
            $publicationScores = [];

            foreach ($goldEntries as $entry) {
                $this->emit("  extracting: " . $entry['doi']);
                try {
                    $extractedRows = $this->extract($entry['pdfPath'], $prompt);
                } catch (Exception $e) {
                    $this->logger->warn("Extraction failed for {$entry['doi']}: " . $e->getMessage());
                    $this->emit("    skipped (extraction error: " . $e->getMessage() . ")");
                    continue;
                }
                $score = $this->scorer->scorePublication($entry['experiments'], $extractedRows);
                $publicationScores[] = $score;
                $this->emit(sprintf("    F1=%.3f (matched %d/%d rows)",
                    $score['f1'], $score['matchedRows'], $score['goldRows']));
            }

            if (empty($publicationScores)) {
                throw new Exception("No publication could be scored in iteration $i.");
            }

            $aggregate = $this->scorer->aggregate($publicationScores);
            $this->emit(sprintf("  aggregate: F1=%.4f P=%.4f R=%.4f",
                $aggregate['f1'], $aggregate['precision'], $aggregate['recall']));

            $timestamp = date('Ymd_His');
            $memory->recordRun([
                'iteration' => $i,
                'timestamp' => $timestamp,
                'topic' => $topic,
                'prompt' => $prompt,
                'metric' => $aggregate,
            ]);
            $history[] = ['iteration' => $i, 'f1' => $aggregate['f1'], 'timestamp' => $timestamp];

            if ($aggregate['f1'] > $best['f1']) {
                $best = ['f1' => $aggregate['f1'], 'prompt' => $prompt];
            }

            if ($i < $iterations) {
                $this->emit("  optimizing prompt for next iteration...");
                $prompt = $this->optimizer->proposeImprovedPrompt(
                    $prompt,
                    $aggregate,
                    $memory->getMemoryText()
                );
            }
        }

        $this->emit(sprintf("\nBest F1 over %d iterations: %.4f", $iterations, $best['f1']));
        return ['best' => $best, 'history' => $history];
    }

    /**
     * Runs one extraction (upload PDF, call AI, parse CSV) and returns the structured rows.
     *
     * @return array<int, array<string,string>>
     * @throws Exception
     */
    public function extract(string $pdfPath, string $prompt): array
    {
        if ($pdfPath === '' || !is_file($pdfPath)) {
            throw new Exception("PDF not found: '$pdfPath'");
        }
        $fileIds = $this->aiClient->uploadFiles([$pdfPath]);
        if (empty($fileIds)) {
            throw new Exception("PDF upload failed: $pdfPath");
        }
        try {
            $response = $this->aiClient->callAI($fileIds, $prompt);
        } finally {
            $this->aiClient->deleteFiles($fileIds);
        }
        return CsvExtractionParser::parseRows($response);
    }

    private function emit(string $msg): void
    {
        ($this->progress)($msg);
    }
}
