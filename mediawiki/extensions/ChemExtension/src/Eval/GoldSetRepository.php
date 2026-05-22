<?php

namespace DIQA\ChemExtension\Eval;

use Exception;

/**
 * Loads the frozen "gold set" — manually curated reference extractions per topic — that the
 * evaluation loop measures the AI extraction against.
 *
 * Layout on disk (under the eval base dir, by default <extension>/eval):
 *
 *   eval/
 *     <Topic_with_underscores>/
 *       gold/
 *         <doi-or-name>.json     one curated publication each
 *       pdfs/
 *         <file>.pdf             the source PDFs referenced by the gold files
 *       runs/                    written by the loop (see EvalMemory)
 *       memory.md                written by the loop (see EvalMemory)
 *
 * Each gold JSON file:
 *   {
 *     "doi": "10.1021/...",
 *     "topic": "Host Guest interaction",
 *     "pdf": "pdfs/foo.pdf",            // relative to the topic dir
 *     "experiments": [                  // one object per experiment row
 *       { "host": "CB[7]", "guest": "adamantane", "ka": "1.2e9", "guest_host_ratio": "1:1" },
 *       ...
 *     ]
 *   }
 *
 * The column keys must be the row-template parameter names (see the corresponding
 * MediaWiki:Prompt_import_<Topic> page), so gold and extraction share one vocabulary.
 */
class GoldSetRepository
{
    private string $baseDir;

    public function __construct(?string $baseDir = null)
    {
        $this->baseDir = $baseDir ?? dirname(__DIR__, 2) . '/eval';
    }

    /**
     * @return string[] topic directory names (with underscores) that contain a gold/ folder
     */
    public function getTopics(): array
    {
        if (!is_dir($this->baseDir)) {
            return [];
        }
        $topics = [];
        foreach (scandir($this->baseDir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (is_dir($this->baseDir . "/$entry/gold")) {
                $topics[] = $entry;
            }
        }
        return $topics;
    }

    /**
     * Loads all gold entries for a topic.
     *
     * @param string $topic topic directory name (underscores)
     * @return array<int, array{doi:string, topic:string, pdfPath:string, experiments:array<int,array<string,string>>}>
     * @throws Exception
     */
    public function loadTopic(string $topic): array
    {
        $topicDir = $this->baseDir . '/' . $topic;
        $goldDir = $topicDir . '/gold';
        if (!is_dir($goldDir)) {
            throw new Exception("No gold set found for topic '$topic' (expected $goldDir)");
        }

        $entries = [];
        foreach (glob($goldDir . '/*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!is_array($data)) {
                throw new Exception("Gold file is not valid JSON: $file");
            }
            $pdfRel = $data['pdf'] ?? '';
            $pdfPath = $pdfRel === '' ? '' : $topicDir . '/' . $pdfRel;
            $entries[] = [
                'doi' => $data['doi'] ?? basename($file, '.json'),
                'topic' => $data['topic'] ?? str_replace('_', ' ', $topic),
                'pdfPath' => $pdfPath,
                'experiments' => $data['experiments'] ?? [],
            ];
        }
        return $entries;
    }

    public function getTopicDir(string $topic): string
    {
        return $this->baseDir . '/' . $topic;
    }
}
