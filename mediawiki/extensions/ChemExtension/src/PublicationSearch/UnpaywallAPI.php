<?php

namespace DIQA\ChemExtension\PublicationSearch;

use DIQA\ChemExtension\Utils\CurlUtil;
use DIQA\ChemExtension\Utils\LoggerUtils;
use Exception;

/**
 * Thin client for the Unpaywall API (https://unpaywall.org/products/api).
 *
 * Used to find a freely available (open access) PDF for a given DOI so that the
 * publication import can prefer open access sources over paywalled ones (issue #343).
 *
 * The API is free but requires an email address as a query parameter. Configure it via
 * $wgCEUnpaywallEmail; it falls back to the wiki's $wgPasswordSender.
 */
class UnpaywallAPI
{
    private $logger;
    private $baseUrl;

    public function __construct()
    {
        $this->logger = new LoggerUtils('UnpaywallAPI', 'ChemExtension');
        $this->baseUrl = 'https://api.unpaywall.org/v2';
    }

    /**
     * Returns the best available open access PDF URL for the given DOI, or null if none.
     *
     * @param string $doi
     * @return string|null
     */
    public function findOpenAccessPdfUrl(string $doi): ?string
    {
        $record = $this->getRecord($doi);
        if ($record === null) {
            return null;
        }
        if (!isset($record->is_oa) || $record->is_oa !== true) {
            $this->logger->log("Unpaywall: no open access version for doi: $doi");
            return null;
        }

        // Prefer best_oa_location, then fall back to scanning all oa_locations.
        $candidates = [];
        if (isset($record->best_oa_location)) {
            $candidates[] = $record->best_oa_location;
        }
        if (isset($record->oa_locations) && is_array($record->oa_locations)) {
            $candidates = array_merge($candidates, $record->oa_locations);
        }

        foreach ($candidates as $location) {
            $url = $location->url_for_pdf ?? null;
            if (is_string($url) && $url !== '') {
                $this->logger->log("Unpaywall: found open access PDF for doi $doi: $url");
                return $url;
            }
        }

        $this->logger->log("Unpaywall: open access record without direct PDF link for doi: $doi");
        return null;
    }

    /**
     * @param string $doi
     * @return object|null decoded Unpaywall record, or null on error / not found
     */
    private function getRecord(string $doi): ?object
    {
        $email = $this->getContactEmail();
        $url = $this->baseUrl . '/' . rawurlencode($doi) . '?' . CurlUtil::buildQueryParams(['email' => $email]);

        $ch = curl_init();
        try {
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            ]);
            $body = curl_exec($ch);
            if (curl_errno($ch)) {
                $this->logger->warn("Unpaywall request failed for doi $doi: " . curl_error($ch));
                return null;
            }
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode < 200 || $httpCode >= 300) {
                $this->logger->warn("Unpaywall returned HTTP $httpCode for doi: $doi");
                return null;
            }
            $parsed = json_decode($body);
            if (!is_object($parsed)) {
                $this->logger->warn("Unpaywall returned unparseable response for doi: $doi");
                return null;
            }
            return $parsed;
        } catch (Exception $e) {
            $this->logger->warn("Unpaywall error for doi $doi: " . $e->getMessage());
            return null;
        } finally {
            curl_close($ch);
        }
    }

    private function getContactEmail(): string
    {
        global $wgCEUnpaywallEmail, $wgPasswordSender;
        return $wgCEUnpaywallEmail ?? $wgPasswordSender ?? 'unpaywall@chemwiki.local';
    }
}
