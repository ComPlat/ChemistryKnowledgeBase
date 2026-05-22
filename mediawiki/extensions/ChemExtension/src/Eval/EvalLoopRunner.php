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
    private ?ProseSimilarityScorer $proseScorer;
    private LoggerUtils $logger;
    /** @var callable */
    private $progress;

    public function __construct(
        ?GoldSetRepository $goldRepo = null,
        ?ExtractionScorer $scorer = null,
        ?AIClient $aiClient = null,
        ?PromptOptimizer $optimizer = null,
        ?callable $progress = null,
        ?ProseSimilarityScorer $proseScorer = null
    ) {
        $this->goldRepo = $goldRepo ?? new GoldSetRepository();
        $this->scorer = $scorer ?? new ExtractionScorer();
        $this->aiClient = $aiClient ?? new AIClient();
        $this->optimizer = $optimizer ?? new PromptOptimizer($this->aiClient);
        $this->proseScorer = $proseScorer;
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
    public function run(string $topic, string $initialPrompt, int $iterations = 5, float $tokenPenalty = 0.0): array
    {
        $goldEntries = $this->goldRepo->loadTopic($topic);
        if (empty($goldEntries)) {
            throw new Exception("Gold set for topic '$topic' is empty.");
        }
        $memory = new EvalMemory($this->goldRepo->getTopicDir($topic));

        $prompt = $initialPrompt;
        $best = ['score' => -INF, 'f1' => -1.0, 'prompt' => $initialPrompt];
        $history = [];

        for ($i = 1; $i <= $iterations; $i++) {
            $this->emit("\n=== Iteration $i/$iterations (topic: $topic) ===");
            $fieldScores = [];
            $totalTokens = 0;
            $scoredPublications = 0;
            $proseSims = [];

            foreach ($goldEntries as $entry) {
                $this->emit("  extracting: " . $entry['doi']);
                try {
                    $extraction = $this->extract($entry['pdfPath'], $prompt);
                } catch (Exception $e) {
                    $this->logger->warn("Extraction failed for {$entry['doi']}: " . $e->getMessage());
                    $this->emit("    skipped (extraction error: " . $e->getMessage() . ")");
                    continue;
                }
                $score = $this->scorer->scorePublication($entry['experiments'], $extraction['rows']);
                $fieldScores[] = $score;
                $scoredPublications++;
                $totalTokens += $extraction['usage']['total'] ?? 0;

                $proseInfo = '';
                if ($this->proseScorer !== null && !empty($entry['prose'])) {
                    $sim = $this->proseScorer->score($extraction['response'], $entry['prose']);
                    $proseSims[] = $sim;
                    $proseInfo = sprintf(" prose=%.3f", $sim);
                }
                $this->emit(sprintf("    F1=%.3f (matched %d/%d rows) tokens=%d%s",
                    $score['f1'], $score['matchedRows'], $score['goldRows'],
                    $extraction['usage']['total'] ?? 0, $proseInfo));
            }

            if ($scoredPublications === 0) {
                throw new Exception("No publication could be scored in iteration $i.");
            }

            $aggregate = $this->scorer->aggregate($fieldScores);
            $avgTokens = (int) round($totalTokens / $scoredPublications);
            $aggregate['tokens'] = [
                'total' => $totalTokens,
                'perPublication' => $avgTokens,
            ];
            $aggregate['f1PerKToken'] = $avgTokens > 0 ? $aggregate['f1'] / ($avgTokens / 1000) : 0.0;
            $aggregate['proseSimilarity'] = empty($proseSims) ? null : array_sum($proseSims) / count($proseSims);

            $this->emit(sprintf("  aggregate: F1=%.4f P=%.4f R=%.4f | tokens/pub=%d | F1/1k=%.3f%s",
                $aggregate['f1'], $aggregate['precision'], $aggregate['recall'], $avgTokens,
                $aggregate['f1PerKToken'],
                $aggregate['proseSimilarity'] !== null ? sprintf(" | prose=%.3f", $aggregate['proseSimilarity']) : ''));

            $timestamp = date('Ymd_His');
            $memory->recordRun([
                'iteration' => $i,
                'timestamp' => $timestamp,
                'topic' => $topic,
                'prompt' => $prompt,
                'metric' => $aggregate,
            ]);
            $history[] = ['iteration' => $i, 'f1' => $aggregate['f1'], 'tokens' => $avgTokens, 'timestamp' => $timestamp];

            // Selection objective: F1 minus an optional efficiency penalty on tokens.
            $selectionScore = $aggregate['f1'] - $tokenPenalty * ($avgTokens / 1000);
            if ($selectionScore > $best['score']) {
                $best = ['score' => $selectionScore, 'f1' => $aggregate['f1'], 'prompt' => $prompt];
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
     * Runs one extraction (upload PDF, call AI, parse CSV).
     *
     * @return array{rows:array<int,array<string,string>>, response:string, usage:array{input:int,output:int,total:int}}
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
        return [
            'rows' => CsvExtractionParser::parseRows($response),
            'response' => $response,
            'usage' => $this->aiClient->getLastUsage() ?? ['input' => 0, 'output' => 0, 'total' => 0],
        ];
    }

    private function emit(string $msg): void
    {
        ($this->progress)($msg);
    }
}
