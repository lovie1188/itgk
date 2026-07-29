<?php

/**
 * AppException - Base Application Exception
 * 
 * All application-specific exceptions should extend this class.
 * Provides structured error handling with context data and HTTP status codes.
 * 
 * @package App\Exceptions
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

class AppException extends Exception
{
    /**
     * HTTP status code for this exception
     * @var int
     */
    protected int $statusCode = 500;

    /**
     * Additional context data for logging
     * @var array
     */
    protected array $context = [];

    /**
     * Error code for API responses
     * @var string
     */
    protected string $errorCode = 'INTERNAL_ERROR';

    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param int $code Error code (for exception)
     * @param array $context Additional context data
     * @param Throwable|null $previous Previous exception
     */
    public function __construct(
        string $message = 'An error occurred',
        int $code = 0,
        array $context = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get HTTP status code
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get error code for API responses
     * 
     * @return string
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get context data
     * 
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Add context data
     * 
     * @param string $key Context key
     * @param mixed $value Context value
     * @return self
     */
    public function addContext(string $key, mixed $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    /**
     * Report this exception to the logger
     * Called by the global error handler
     * 
     * @return void
     */
    public function report(): void
    {
        $context = array_merge([
            'exception' => get_class($this),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        ], $this->context);

        \App\Helpers\Logger::error($this->getMessage(), $context);
    }

    /**
     * Convert exception to array for API response
     * 
     * @param bool $debug Include debug information
     * @return array
     */
    public function toArray(bool $debug = false): array
    {
        $result = [
            'success' => false,
            'error' => [
                'code' => $this->errorCode,
                'message' => $this->getMessage(),
                'status' => $this->statusCode
            ]
        ];

        if ($debug) {
            $result['debug'] = [
                'file' => $this->getFile(),
                'line' => $this->getLine(),
                'trace' => array_slice(explode("\n", $this->getTraceAsString()), 0, 10)
            ];
        }

        return $result;
    }
}
