<?php

namespace DIQA\ChemExtension\PublicationSearch;

use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

class DownloadLinkFinder
{
    /**
     * Common file extensions considered as downloadable files.
     */
    private const DOWNLOAD_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'zip', 'tar', 'gz',
        'rar', '7z', 'exe', 'msi', 'dmg', 'iso', 'ppt', 'pptx', 'txt',
        'rtf', 'odt', 'ods', 'odp', 'epub', 'mp3', 'mp4', 'avi', 'mov',
    ];

    private string $url;
    private string $html;
    private LoggerUtils $logger;

    public function __construct(string $url)
    {
        $this->url = $url;
        $this->logger = new LoggerUtils('DownloadLinkFinder', 'ChemExtension');
    }

    /**
     * Downloads the HTML page and returns all download links found on it.
     *
     * @param string[] $extensions Optional list of file extensions to look for.
     *                             If empty, the default list is used.
     * @return array<int, array{url: string, text: string, extension: string}>
     * @throws Exception
     */
    public function findDownloadLinks(array $extensions = []): array
    {
        $this->html = $this->downloadPage();
        $links = $this->extractLinks();

        return $this->filterDownloadLinks(
            $links,
            $extensions ?: self::DOWNLOAD_EXTENSIONS,
            'download'
        );
    }

    /**
     * Downloads the HTML content from the URL.
     *
     * @return string
     * @throws Exception
     */
    private function downloadPage(): string
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml',
                'User-Agent: MediaWiki/DownloadLinkFinder',
            ],
        ]);

        try {
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new Exception(
                    'Failed to download page: ' . curl_error($ch)
                );
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode < 200 || $httpCode >= 300) {
                throw new Exception(
                    "HTTP request failed with status code $httpCode for URL: {$this->url}"
                );
            }

            if ($response === false || $response === '') {
                throw new Exception(
                    "Empty response received from URL: {$this->url}"
                );
            }

            return $response;
        } finally {
            curl_close($ch);
        }
    }

    /**
     * Extracts all <a> elements from the HTML.
     *
     * @return array<int, array{url: string, text: string}>
     */
    private function extractLinks(): array
    {
        $dom = new \DOMDocument();

        // Suppress warnings for malformed HTML
        $previousUseErrors = libxml_use_internal_errors(true);
        $dom->loadHTML($this->html, LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previousUseErrors);

        $links = [];
        $anchors = $dom->getElementsByTagName('a');

        /** @var \DOMElement $anchor */
        foreach ($anchors as $anchor) {
            $href = trim($anchor->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#')) {
                continue;
            }

            $absoluteUrl = $this->resolveUrl($href);
            $links[] = [
                'url'  => $absoluteUrl,
                'text' => trim($anchor->textContent),
            ];
        }

        return $links;
    }

    /**
     * Filters links to keep only those pointing to downloadable files.
     *
     * A link is considered a download link if:
     *  - its URL path ends with one of the given extensions, or
     *  - it has a `download` attribute, or
     *  - its link text contains common download-related keywords.
     *
     * @param array<int, array{url: string, text: string}> $links
     * @param string[] $extensions
     * @return array<int, array{url: string, text: string, extension: string}>
     */
    private function filterDownloadLinks(array $links, array $extensions, $text = ''): array
    {
        $extensionPattern = implode('|', array_map('preg_quote', $extensions));

        $downloadLinks = [];
        foreach ($links as $link) {
            $parsedUrl = parse_url($link['url'], PHP_URL_PATH) ?? '';
            // Remove query string artifacts and decode
            $path = urldecode($parsedUrl);

            if (preg_match('/\.(' . $extensionPattern . ')$/i', $path, $matches)) {
                $downloadLinks[] = [
                    'url'       => $link['url'],
                    'text'      => $link['text'],
                    'extension' => strtolower($matches[1]),
                ];
            } else if (str_contains(strtolower($link['text']), $text)) {
                $downloadLinks[] = [
                    'url'       => $link['url'],
                    'text'      => $link['text'],
                    'extension' => '',
                ];
            }
        }

        return $downloadLinks;
    }

    /**
     * Resolves a potentially relative URL against the base URL.
     *
     * @param string $href
     * @return string
     */
    private function resolveUrl(string $href): string
    {
        // Already absolute
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        // Protocol-relative
        if (str_starts_with($href, '//')) {
            $scheme = parse_url($this->url, PHP_URL_SCHEME) ?? 'https';
            return $scheme . ':' . $href;
        }

        $parsed = parse_url($this->url);
        $scheme = $parsed['scheme'] ?? 'https';
        $host   = $parsed['host'] ?? '';
        $port   = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $base   = "$scheme://$host$port";

        // Absolute path
        if (str_starts_with($href, '/')) {
            return $base . $href;
        }

        // Relative path — resolve against the directory of the current URL
        $path = $parsed['path'] ?? '/';
        $dir  = substr($path, 0, (int)strrpos($path, '/'));

        return $base . $dir . '/' . $href;
    }
}