<?php

namespace DIQA\ChemExtension\PublicationSearch;

use DIQA\ChemExtension\Utils\CurlUtil;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

class OpenAlexAPI extends PublicationFetcher {

    private $logger;
    private $openAlexApiBaseUrl;

    public function __construct()
    {
        $this->logger = new LoggerUtils('OpenAlex', 'ChemExtension');
        $this->openAlexApiBaseUrl = 'https://api.openalex.org';
    }

    /**
     * Fetches the latest publications from OpenAlex. The callback is invoked
     * once per page with an array of {@link PublicationSearchResult}.
     *
     * @param callable $callback function(PublicationSearchResult[] $results): void
     * @param int $daysBack Number of days to look back (default 1).
     */
    public function fetchPublication(callable $callback, $daysBack = 1): void
    {
        $pageNumber = 0;
        $pageSize = 100;
        $nextCursor = '*';

        do {
            $this->logger->log("\nFetching page $pageNumber...");
            $res = $this->fetchPublications($daysBack, $pageSize, $nextCursor);
            $callback($res['results']);
            $nextCursor = $res['nextCursor'];
            $pageNumber++;
        } while (!empty($nextCursor) && count($res['results']) === $pageSize);
    }

    /**
     * @return array{results: PublicationSearchResult[], nextCursor: ?string}
     * @throws Exception
     */
    private function fetchPublications(int $daysAgo, int $pageSize, ?string $cursor): array
    {
        $fromDate = date('Y-m-d', strtotime("-$daysAgo days"));
        $untilDate = date('Y-m-d');

        $filters = [
            'from_publication_date:' . $fromDate,
            'to_publication_date:' . $untilDate,
            'type:article',
            'has_doi:true',
            'topics.domain.id:3', // Physical Sciences
            'topics.field.id:16' // Chemistry
        ];

        $params = [
            'filter' => implode(',', $filters),
            'per-page' => $pageSize,
            'cursor' => $cursor ?? '*',
            'sort' => 'publication_date:desc',
            'select' => 'doi,title,abstract_inverted_index,publication_date,primary_location',
        ];

        $json = $this->getJsonData('/works', $params);

        $results = $this->parseResults($json);
        $nextCursor = $json->meta->next_cursor ?? null;

        return ['results' => $results, 'nextCursor' => $nextCursor];
    }

    /**
     * @return PublicationSearchResult[]
     */
    private function parseResults($json): array
    {
        $results = [];
        $items = $json->results ?? [];
        foreach ($items as $item) {
            $doi = $this->normalizeDoi($item->doi ?? null);
            if ($doi === null || empty($item->title)) {
                continue;
            }
            $title = $item->title;
            $abstract = $this->reconstructAbstract($item->abstract_inverted_index ?? null) ?? $title;
            $published = $item->publication_date ?? null;
            $journal = $item->primary_location->source->display_name ?? '';

            $results[] = new PublicationSearchResult(
                $doi,
                $title,
                strip_tags($abstract),
                $published,
                null,
                null,
                null,
                $journal
            );
        }
        return $results;
    }

    /**
     * OpenAlex returns DOIs as full URLs (e.g. https://doi.org/10.xxx/yyy).
     * Strip the URL prefix so it matches the format used elsewhere.
     */
    private function normalizeDoi(?string $doi): ?string
    {
        if ($doi === null || $doi === '') {
            return null;
        }
        return preg_replace('#^https?://(dx\.)?doi\.org/#i', '', $doi);
    }

    /**
     * OpenAlex stores abstracts as an inverted index: { "word": [positions...] }.
     * Reconstruct the original abstract text.
     */
    private function reconstructAbstract($invertedIndex): ?string
    {
        if (!is_object($invertedIndex) && !is_array($invertedIndex)) {
            return null;
        }
        $positions = [];
        foreach ((array)$invertedIndex as $word => $indices) {
            foreach ($indices as $i) {
                $positions[$i] = $word;
            }
        }
        if (empty($positions)) {
            return null;
        }
        ksort($positions);
        return implode(' ', $positions);
    }

    /**
     * @throws Exception
     */
    private function getJsonData(string $path, array $queryParams = [])
    {
        $ch = curl_init();
        try {
            $url = $this->openAlexApiBaseUrl . $path . '?' . CurlUtil::buildQueryParams($queryParams);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:', 'Accept: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception("Error on request: " . curl_error($ch) . " for $url");
            }
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            [$header, $body] = CurlUtil::splitResponse($response);
            $parsedBody = json_decode($body);

            if ($httpcode >= 200 && $httpcode <= 299) {
                return $parsedBody;
            }
            $errMsg = $parsedBody->error ?? $parsedBody->message ?? $body;
            throw new Exception("OpenAlex request failed. HTTP status: $httpcode. Message: $errMsg for $url");
        } finally {
            curl_close($ch);
        }
    }
}