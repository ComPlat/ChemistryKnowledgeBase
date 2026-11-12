<?php

namespace DIQA\ChemExtension\PublicationImport;

use DIQA\ChemExtension\Utils\LoggerUtils;

/**
 * Minimal JSON-over-HTTP transport for OpenAI-compatible APIs, built on curl.
 *
 * - Bearer authentication (omitted when no key is configured, e.g. local servers)
 * - Retries transient failures (transport errors, 408/409/429/5xx) with exponential backoff
 *   and jitter, honouring a Retry-After header
 * - Concurrent POSTs via curl_multi for ensemble extraction; failed passes are retried
 *   individually so one rate-limited pass does not sink the whole ensemble
 */
class AIHttpTransport
{
    private const CONNECT_TIMEOUT_SECONDS = 30;
    private const MAX_BACKOFF_SECONDS = 60;
    private const ERROR_SNIPPET_LENGTH = 1000;

    private AIProviderConfig $config;
    private LoggerUtils $logger;
    /** @var callable(float):void */
    private $sleep;

    public function __construct(AIProviderConfig $config, LoggerUtils $logger, ?callable $sleep = null)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->sleep = $sleep ?? static function (float $seconds): void {
            usleep((int)($seconds * 1_000_000));
        };
    }

    /**
     * @return array decoded JSON response
     * @throws AIRequestException after the last failed attempt
     */
    public function postJson(string $path, array $body): array
    {
        $payload = self::encode($body);
        $attempt = 0;
        while (true) {
            $ch = $this->createHandle('POST', $path, $payload, $headers);
            $response = curl_exec($ch);
            try {
                return $this->decodeHandle($ch, $headers, is_string($response) ? $response : '');
            } catch (AIRequestException $e) {
                if (!$e->isRetryable() || $attempt >= $this->config->getMaxRetries()) {
                    throw $e;
                }
                $this->backoff($attempt++, $e);
            } finally {
                curl_close($ch);
            }
        }
    }

    /**
     * @return array decoded JSON response
     * @throws AIRequestException
     */
    public function getJson(string $path): array
    {
        $ch = $this->createHandle('GET', $path, null, $headers);
        $response = curl_exec($ch);
        try {
            return $this->decodeHandle($ch, $headers, is_string($response) ? $response : '');
        } finally {
            curl_close($ch);
        }
    }

    /**
     * Fires $n identical POSTs concurrently. Passes that fail with a retryable error are
     * retried sequentially (with backoff) afterwards.
     *
     * @return array<int, array{data: ?array, error: ?string}> in request order
     */
    public function postJsonParallel(string $path, array $body, int $n): array
    {
        $payload = self::encode($body);
        $multi = curl_multi_init();
        $handles = [];
        $headers = [];
        for ($i = 0; $i < $n; $i++) {
            $handles[$i] = $this->createHandle('POST', $path, $payload, $headers[$i]);
            curl_multi_add_handle($multi, $handles[$i]);
        }

        $running = 0;
        do {
            $status = curl_multi_exec($multi, $running);
            if ($running > 0) {
                curl_multi_select($multi, 1.0);
            }
        } while ($running > 0 && $status === CURLM_OK);

        // curl_errno() is not reliable for handles driven by curl_multi; take the per-handle
        // result code from the multi info queue instead.
        $results = [];
        $curlCodes = [];
        while ($info = curl_multi_info_read($multi)) {
            $index = array_search($info['handle'], $handles, true);
            if ($index !== false) {
                $curlCodes[$index] = $info['result'];
            }
        }

        foreach ($handles as $i => $ch) {
            try {
                $results[$i] = ['data' => $this->decodeHandle($ch, $headers[$i],
                    (string)curl_multi_getcontent($ch), $curlCodes[$i] ?? null), 'error' => null];
            } catch (AIRequestException $e) {
                $results[$i] = ['data' => null, 'error' => $e];
            }
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }
        curl_multi_close($multi);

        foreach ($results as $i => $result) {
            $error = $result['error'];
            if ($error instanceof AIRequestException && $error->isRetryable() && $this->config->getMaxRetries() > 0) {
                $this->logger->warn("Parallel pass " . ($i + 1) . " failed (" . $error->getMessage() . "), retrying");
                $this->backoff(0, $error);
                try {
                    $results[$i] = ['data' => $this->postJson($path, $body), 'error' => null];
                } catch (AIRequestException $e) {
                    $results[$i] = ['data' => null, 'error' => $e];
                }
            }
        }

        return array_map(static fn(array $r) => [
            'data' => $r['data'],
            'error' => $r['error'] instanceof AIRequestException ? $r['error']->getMessage() : null,
        ], $results);
    }

    /**
     * Exponential backoff with full jitter, capped; a server-provided Retry-After wins.
     */
    public static function backoffSeconds(int $attempt, ?int $retryAfter, float $random): float
    {
        if ($retryAfter !== null && $retryAfter > 0) {
            return (float)min($retryAfter, self::MAX_BACKOFF_SECONDS);
        }
        $cap = min(self::MAX_BACKOFF_SECONDS, 2 ** ($attempt + 1));
        return max(0.5, $cap * $random);
    }

    private function backoff(int $attempt, AIRequestException $e): void
    {
        $seconds = self::backoffSeconds($attempt, $e->getRetryAfterSeconds(), mt_rand() / mt_getrandmax());
        $this->logger->warn(sprintf("AI request failed (%s), retry %d/%d in %.1fs",
            $e->getMessage(), $attempt + 1, $this->config->getMaxRetries(), $seconds));
        ($this->sleep)($seconds);
    }

    /**
     * @param array|null $headers receives the response headers (lower-cased names)
     * @return resource|\CurlHandle
     */
    private function createHandle(string $method, string $path, ?string $payload, ?array &$headers)
    {
        $headers = [];
        $requestHeaders = ['Content-Type: application/json', 'Accept: application/json'];
        if ($this->config->getApiKey() !== null) {
            $requestHeaders[] = 'Authorization: Bearer ' . $this->config->getApiKey();
        }
        $ch = curl_init($this->config->getBaseUrl() . '/' . ltrim($path, '/'));
        $options = [
            CURLOPT_HTTPHEADER => $requestHeaders,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config->getTimeout(),
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$headers): int {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return strlen($line);
            },
        ];
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $payload;
        }
        curl_setopt_array($ch, $options);
        return $ch;
    }

    /**
     * @param resource|\CurlHandle $ch
     * @param string   $body     response body
     * @param int|null $curlCode result code from curl_multi_info_read (parallel requests)
     * @throws AIRequestException
     */
    private function decodeHandle($ch, array $headers, string $body, ?int $curlCode = null): array
    {
        $curlCode = $curlCode ?? curl_errno($ch);
        if ($curlCode !== CURLE_OK) {
            $message = curl_error($ch) ?: curl_strerror($curlCode);
            throw new AIRequestException("curl error: $message", 0);
        }
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status < 200 || $status >= 300) {
            $retryAfter = isset($headers['retry-after']) && is_numeric($headers['retry-after'])
                ? (int)$headers['retry-after'] : null;
            throw new AIRequestException(
                "HTTP $status: " . mb_substr($body, 0, self::ERROR_SNIPPET_LENGTH),
                $status, $body, $retryAfter
            );
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new AIRequestException("Malformed JSON response: " . mb_substr($body, 0, 200), $status, $body);
        }
        return $decoded;
    }

    private static function encode(array $body): string
    {
        $payload = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($payload === false) {
            throw new AIRequestException('Could not encode request body: ' . json_last_error_msg());
        }
        return $payload;
    }
}
