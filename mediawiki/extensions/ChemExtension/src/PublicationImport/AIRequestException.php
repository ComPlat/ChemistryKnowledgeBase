<?php

namespace DIQA\ChemExtension\PublicationImport;

use RuntimeException;

/**
 * A failed HTTP request to an LLM backend. Carries the HTTP status (0 for transport errors)
 * and the response body so callers can tell transient failures from permanent ones.
 */
class AIRequestException extends RuntimeException
{
    private int $httpStatus;
    private string $responseBody;
    private ?int $retryAfterSeconds;

    public function __construct(string $message, int $httpStatus = 0, string $responseBody = '',
                                ?int $retryAfterSeconds = null)
    {
        parent::__construct($message, $httpStatus);
        $this->httpStatus = $httpStatus;
        $this->responseBody = $responseBody;
        $this->retryAfterSeconds = $retryAfterSeconds;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    public function getRetryAfterSeconds(): ?int
    {
        return $this->retryAfterSeconds;
    }

    /**
     * Transport errors, timeouts, rate limits and server errors are worth retrying;
     * other 4xx responses (bad request, auth, unknown model) are not.
     */
    public function isRetryable(): bool
    {
        return self::isRetryableStatus($this->httpStatus);
    }

    public static function isRetryableStatus(int $status): bool
    {
        return $status === 0 || $status === 408 || $status === 409 || $status === 429 || $status >= 500;
    }
}
