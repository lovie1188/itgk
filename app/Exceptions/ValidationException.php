<?php

/**
 * ValidationException - Input Validation Error
 * 
 * Thrown when input validation fails. Contains field-specific error messages.
 * 
 * @package App\Exceptions
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Exceptions;

class ValidationException extends AppException
{
    /**
     * Field-specific validation errors
     * @var array
     */
    private array $fieldErrors = [];

    /**
     * Constructor
     * 
     * @param string $message Overall error message
     * @param array $errors Field-specific errors ['field' => 'message']
     */
    public function __construct(string $message = 'Validation failed', array $errors = [])
    {
        parent::__construct($message, 422);
        $this->statusCode = 422;
        $this->errorCode = 'VALIDATION_ERROR';
        $this->fieldErrors = $errors;
    }

    /**
     * Get field-specific errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->fieldErrors;
    }

    /**
     * Get error for specific field
     * 
     * @param string $field Field name
     * @return string|null Error message or null
     */
    public function getError(string $field): ?string
    {
        return $this->fieldErrors[$field] ?? null;
    }

    /**
     * Check if field has error
     * 
     * @param string $field Field name
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->fieldErrors[$field]);
    }

    /**
     * Add field error
     * 
     * @param string $field Field name
     * @param string $message Error message
     * @return self
     */
    public function addError(string $field, string $message): self
    {
        $this->fieldErrors[$field] = $message;
        return $this;
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
        $result['error']['errors'] = $this->fieldErrors;
        return $result;
    }
}
