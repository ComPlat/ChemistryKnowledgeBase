<?php

namespace DIQA\ChemExtension\Eval;

/**
 * Builds gold-set entries from a MediaWiki XML export of the curated publication and investigation
 * pages. This turns the team's existing manual curation directly into the evaluation gold set — no
 * re-entry needed.
 *
 *  - publications.xml provides, per publication page: the DOI ({{DOI|doi=...}}) and topic categories.
 *  - investigations.xml provides the curated experiments as row-template calls
 *    ({{Photocatalytic_CO2_conversion |catalyst=Molecule:123 |cat conc=... ...}}); the page title is
 *    "<Publication title>/<Table…>", which links each table back to its publication.
 *
 * Molecule values are stored as "Molecule:<id>" — the same canonical form
 * {@see MoleculeResolver} produces — so molecule comparison works directly.
 *
 * Pure (no MediaWiki runtime), so it is unit-testable; the maintenance script handles file IO and
 * PDF fetching.
 */
class GoldSetFromXml
{
    /** row-template name => eval topic directory */
    private const ROW_TEMPLATE_TOPIC = [
        'Photocatalytic_CO2_conversion' => 'Photocatalytic_CO2_conversion',
        'EC_conversion_of_CO2' => 'Electrochemical_CO2_conversion',
        'Host_Guest_interaction' => 'Host_Guest_interaction',
    ];

    private const SKIP_PARAMS = ['include', 'details', 'BasePageName'];

    /**
     * @return array<int, array{doi:string, topic:string, title:string, experiments:array<int,array<string,string>>}>
     */
    public static function build(string $publicationsXml, string $investigationsXml): array
    {
        $pubs = self::parsePublications($publicationsXml); // normTitle => doi
        $invByPub = self::parseInvestigations($investigationsXml); // normTitle => [topicDir, rows[]]

        $entries = [];
        foreach ($invByPub as $normTitle => $info) {
            $doi = $pubs[$normTitle] ?? null;
            if ($doi === null || empty($info['rows'])) {
                continue;
            }
            $entries[] = [
                'doi' => $doi,
                'topic' => $info['topic'],
                'title' => $info['title'],
                'experiments' => $info['rows'],
            ];
        }
        return $entries;
    }

    /** @return array<string,string> normalized title => DOI */
    private static function parsePublications(string $xml): array
    {
        $result = [];
        foreach (self::pages($xml) as [$title, $text]) {
            if (preg_match('/\{\{DOI\s*\|\s*doi\s*=\s*([^|}\s]+)/i', $text, $m)) {
                $result[self::normTitle($title)] = trim($m[1]);
            }
        }
        return $result;
    }

    /** @return array<string, array{topic:string, title:string, rows:array<int,array<string,string>>}> */
    private static function parseInvestigations(string $xml): array
    {
        $byPub = [];
        foreach (self::pages($xml) as [$title, $text]) {
            $pubTitle = explode('/', $title)[0];
            $norm = self::normTitle($pubTitle);
            foreach (self::ROW_TEMPLATE_TOPIC as $rowTemplate => $topicDir) {
                $rows = self::parseRows($text, $rowTemplate);
                if (empty($rows)) {
                    continue;
                }
                if (!isset($byPub[$norm])) {
                    $byPub[$norm] = ['topic' => $topicDir, 'title' => $pubTitle, 'rows' => []];
                }
                $byPub[$norm]['rows'] = array_merge($byPub[$norm]['rows'], $rows);
            }
        }
        return $byPub;
    }

    /**
     * Extracts the row-template calls of one template from a page's wikitext.
     *
     * @return array<int, array<string,string>>
     */
    public static function parseRows(string $text, string $rowTemplate): array
    {
        // match {{RowTemplate<whitespace/pipe> ... }} — the row name must be followed by a
        // non-word char so "..._experiments" wrappers are not matched.
        $pattern = '/\{\{' . preg_quote($rowTemplate, '/') . '(?=[\s|])(.*?)\}\}/s';
        if (!preg_match_all($pattern, $text, $matches)) {
            return [];
        }
        $rows = [];
        foreach ($matches[1] as $block) {
            $row = [];
            foreach (explode("\n", $block) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] !== '|') {
                    continue;
                }
                $line = ltrim($line, '|');
                $eq = strpos($line, '=');
                if ($eq === false) {
                    continue;
                }
                $key = trim(substr($line, 0, $eq));
                $value = trim(substr($line, $eq + 1));
                if ($key === '' || $value === '' || in_array($key, self::SKIP_PARAMS, true)) {
                    continue;
                }
                $row[$key] = $value;
            }
            if (!empty($row)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /** @return array<int, array{0:string,1:string}> list of [title, decoded text] per <page> */
    private static function pages(string $xml): array
    {
        $pages = [];
        if (!preg_match_all('/<page\b.*?>(.*?)<\/page>/s', $xml, $blocks)) {
            return $pages;
        }
        foreach ($blocks[1] as $block) {
            if (!preg_match('/<title>(.*?)<\/title>/s', $block, $t)) {
                continue;
            }
            $title = html_entity_decode($t[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $text = '';
            if (preg_match('/<text\b[^>]*>(.*?)<\/text>/s', $block, $x)) {
                $text = html_entity_decode($x[1], ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
            $pages[] = [$title, $text];
        }
        return $pages;
    }

    private static function normTitle(string $title): string
    {
        return preg_replace('/\s+/', ' ', trim($title));
    }
}
