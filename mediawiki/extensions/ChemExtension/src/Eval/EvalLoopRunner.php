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
    private ?array $jsonSchema = null;
    private string $schemaName = 'extraction';
    private ?array $sanityRules = null;
    private SanityChecker $sanityChecker;
    private int $visionMaxPages = 0;
    private ?PdfPageRenderer $pageRenderer = null;
    private ?ExtractionCritic $critic = null;

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
        $this->sanityChecker = new SanityChecker();
    }

    /**
     * Switches extraction to structured outputs (JSON schema) instead of CSV-in-prose.
     */
    public function useStructuredOutput(array $jsonSchema, string $schemaName = 'extraction'): void
    {
        $this->jsonSchema = $jsonSchema;
        $this->schemaName = $schemaName;
    }

    /**
     * Enables deterministic plausibility checks on each extraction (rules from TopicProfile).
     */
    public function useSanityRules(array $rules): void
    {
        $this->sanityRules = $rules;
    }

    /**
     * Attaches the first $maxPages rendered PDF pages as vision input (in addition to the
     * uploaded document), if a page renderer is available. 0 disables vision.
     */
    public function useVision(int $maxPages, ?PdfPageRenderer $renderer = null): void
    {
        $this->visionMaxPages = max(0, $maxPages);
        $this->pageRenderer = $renderer ?? new PdfPageRenderer();
    }

    /**
     * Enables a second-pass critic that scores each extracted row's confidence against the source.
     */
    public function useCritic(float $threshold): void
    {
        $this->critic = new ExtractionCritic($this->aiClient, $threshold);
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
            $sanityChecks = 0;
            $sanityFailed = 0;
            $confidences = [];
            $flaggedTotal = 0;

            foreach ($goldEntries as $entry) {
                $this->emit("  extracting: " . $entry['doi']);
                try {
                    $extraction = $this->extract($entry['pdfPaths'], $prompt);
                } catch (Exception $e) {
                    $this->logger->warn("Extraction failed for {$entry['doi']}: " . $e->getMessage());
                    $this->emit("    skipped (extraction error: " . $e->getMessage() . ")");
                    continue;
                }
                $score = $this->scorer->scorePublication($entry['experiments'], $extraction['rows']);
                $fieldScores[] = $score;
                $scoredPublications++;
                $totalTokens += $extraction['usage']['total'] ?? 0;

                if ($this->sanityRules !== null) {
                    $sanity = $this->sanityChecker->check($this->sanityRules, $extraction['rows']);
                    $sanityChecks += $sanity['checks'];
                    $sanityFailed += $sanity['failed'];
                }

                if (($extraction['confidence'] ?? null) !== null) {
                    $confidences[] = $extraction['confidence'];
                    $flaggedTotal += $extraction['flaggedRows'] ?? 0;
                }

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
            $aggregate['sanityPassRate'] = $sanityChecks > 0 ? 1.0 - $sanityFailed / $sanityChecks : null;
            $aggregate['avgConfidence'] = empty($confidences) ? null : array_sum($confidences) / count($confidences);
            $aggregate['flaggedRows'] = $flaggedTotal;

            $this->emit(sprintf("  aggregate: F1=%.4f P=%.4f R=%.4f | tokens/pub=%d | F1/1k=%.3f%s%s%s%s",
                $aggregate['f1'], $aggregate['precision'], $aggregate['recall'], $avgTokens,
                $aggregate['f1PerKToken'],
                $aggregate['unitCorrectness'] !== null ? sprintf(" | units=%.3f", $aggregate['unitCorrectness']) : '',
                $aggregate['sanityPassRate'] !== null ? sprintf(" | sanity=%.3f", $aggregate['sanityPassRate']) : '',
                $aggregate['avgConfidence'] !== null ? sprintf(" | conf=%.3f (flagged %d)", $aggregate['avgConfidence'], $flaggedTotal) : '',
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
     * Runs one extraction (upload the article + any supplementary PDFs, optionally attach rendered
     * page images for vision, call the AI, parse the result).
     *
     * @param string[] $pdfPaths article PDF plus optional supplementary files
     * @return array{rows:array<int,array<string,string>>, response:string, usage:array{input:int,output:int,total:int}}
     * @throws Exception
     */
    public function extract(array $pdfPaths, string $prompt): array
    {
        $existing = array_values(array_filter($pdfPaths, 'is_file'));
        if (empty($existing)) {
            throw new Exception("No PDF found: '" . implode(', ', $pdfPaths) . "'");
        }
        $fileIds = $this->aiClient->uploadFiles($existing);
        if (empty($fileIds)) {
            throw new Exception("PDF upload failed: " . implode(', ', $existing));
        }

        $images = [];
        $imageFileIds = [];
        if ($this->visionMaxPages > 0 && $this->pageRenderer !== null) {
            foreach ($existing as $pdf) {
                $images = array_merge($images, $this->pageRenderer->renderPages($pdf, $this->visionMaxPages));
            }
            if (!empty($images)) {
                $imageFileIds = $this->aiClient->uploadFiles($images);
            }
        }

        $confidence = null;
        $flaggedRows = 0;
        try {
            if ($this->jsonSchema !== null) {
                $raw = $this->aiClient->callAIWithSchema($fileIds, $prompt, $this->jsonSchema, $this->schemaName, $imageFileIds);
                $parsed = StructuredExtractionParser::parse($raw);
                $rows = $parsed['rows'];
                $proseText = $parsed['summary'];
            } else {
                $response = $this->aiClient->callAI($fileIds, $prompt, $imageFileIds);
                $rows = CsvExtractionParser::parseRows($response);
                $proseText = $response;
            }
            // include token usage of the extraction call before the critic adds its own
            $usage = $this->aiClient->getLastUsage() ?? ['input' => 0, 'output' => 0, 'total' => 0];

            if ($this->critic !== null && !empty($rows)) {
                $review = $this->critic->reviewWithFiles($fileIds, $rows, $imageFileIds);
                $confidence = $review['avgConfidence'];
                $flaggedRows = count($review['flagged']);
            }
        } finally {
            $this->aiClient->deleteFiles(array_merge($fileIds, $imageFileIds));
            if (!empty($images) && $this->pageRenderer !== null) {
                $this->pageRenderer->cleanup($images);
            }
        }
        return [
            'rows' => $rows,
            'response' => $proseText,
            'usage' => $usage,
            'confidence' => $confidence,
            'flaggedRows' => $flaggedRows,
        ];
    }

    private function emit(string $msg): void
    {
        ($this->progress)($msg);
    }
}
