<?php

/**
 * AuthException - Authentication/Authorization Error
 * 
 * Thrown when authentication fails or user lacks required permissions.
 * 
 * @package App\Exceptions
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Exceptions;

class AuthException extends AppException
{
    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code (401 for auth, 403 for forbidden)
     */
    public function __construct(string $message = 'Unauthorized', int $statusCode = 401)
    {
        parent::__construct($message, $statusCode);
        $this->statusCode = $statusCode;
        $this->errorCode = $statusCode === 401 ? 'AUTHENTICATION_ERROR' : 'AUTHORIZATION_ERROR';
    }

    /**
     * Create authentication required exception
     * 
     * @return self
     */
    public static function notAuthenticated(): self
    {
        return new self('Authentication required', 401);
    }

    /**
     * Create invalid credentials exception
     * 
     * @return self
     */
    public static function invalidCredentials(): self
    {
        return new self('Invalid username or password', 401);
    }

    /**
     * Create access denied exception
     * 
     * @param string|null $requiredRole Required role
     * @return self
     */
    public static function accessDenied(?string $requiredRole = null): self
    {
        $message = $requiredRole
            ? "Access denied. Required role: {$requiredRole}"
            : 'Access denied';
        return new self($message, 403);
    }

    /**
     * Create permission denied exception
     * 
     * @param string|null $permission Required permission
     * @return self
     */
    public static function permissionDenied(?string $permission = null): self
    {
        $message = $permission
            ? "Permission denied. Required: {$permission}"
            : 'Permission denied';
        return new self($message, 403);
    }

    /**
     * Create token expired exception
     * 
     * @return self
     */
    public static function tokenExpired(): self
    {
        return new self('Authentication token has expired', 401);
    }

    /**
     * Create invalid token exception
     * 
     * @return self
     */
    public static function invalidToken(): self
    {
        return new self('Invalid authentication token', 401);
    }

    /**
     * Create session expired exception
     * 
     * @return self
     */
    public static function sessionExpired(): self
    {
        return new self('Session has expired. Please log in again.', 401);
    }
}
