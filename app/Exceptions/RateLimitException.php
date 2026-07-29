<?php

/**
 * RateLimitException - Rate Limiting Error
 * 
 * Thrown when rate limit is exceeded.
 * 
 * @package App\Exceptions
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Exceptions;

class RateLimitException extends AppException
{
    /**
     * Seconds until rate limit resets
     * @var int
     */
    private int $retryAfter;

    /**
     * Maximum allowed attempts
     * @var int
     */
    private int $maxAttempts;

    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param int $retryAfter Seconds until retry
     * @param int $maxAttempts Maximum allowed attempts
     */
    public function __construct(
        string $message = 'Too many requests',
        int $retryAfter = 60,
        int $maxAttempts = 60
    ) {
        parent::__construct($message, 429);
        $this->statusCode = 429;
        $this->errorCode = 'RATE_LIMIT_EXCEEDED';
        $this->retryAfter = $retryAfter;
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Get seconds until retry
     * 
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * Get maximum allowed attempts
     * 
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /**
     * Convert to array for API response
     * 
     * @param bool $debug Include debug information
     * @return array
     */
    public function toArray(bool $debug = false): array
    {
        $result = parent::toArray($debug);
        $result['error']['retry_after'] = $this->retryAfter;
        $result['error']['max_attempts'] = $this->maxAttempts;
        return $result;
    }
}
