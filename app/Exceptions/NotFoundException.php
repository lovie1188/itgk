<?php

/**
 * NotFoundException - Resource Not Found Error
 * 
 * Thrown when a requested resource cannot be found.
 * 
 * @package App\Exceptions
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Exceptions;

class NotFoundException extends AppException
{
    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param string|null $resource Resource type that was not found
     */
    public function __construct(string $message = 'Resource not found', ?string $resource = null)
    {
        parent::__construct($message, 404);
        $this->statusCode = 404;
        $this->errorCode = 'NOT_FOUND';

        if ($resource) {
            $this->addContext('resource', $resource);
        }
    }

    /**
     * Create exception for specific resource type
     * 
     * @param string $resource Resource type (e.g., 'Certificate', 'Learner')
     * @param int|string|null $id Resource identifier
     * @return self
     */
    public static function forResource(string $resource, int|string|null $id = null): self
    {
        $message = $id
            ? "{$resource} with ID '{$id}' not found"
            : "{$resource} not found";

        $exception = new self($message, $resource);
        if ($id !== null) {
            $exception->addContext('id', $id);
        }
        return $exception;
    }

    /**
     * Create exception for route not found
     * 
     * @param string $route Route that was not found
     * @return self
     */
    public static function route(string $route): self
    {
        return new self("Route not found: {$route}", 'Route');
    }
}
