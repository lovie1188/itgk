<?php

/**
 * MiddlewareInterface - Contract for Middleware Classes
 * 
 * All middleware classes must implement this interface.
 * Middleware provides a convenient mechanism for filtering
 * HTTP requests entering your application.
 * 
 * @package App\Middleware
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Middleware;

interface MiddlewareInterface
{
    /**
     * Handle the middleware request
     * 
     * This method should:
     * - Perform the middleware action (auth check, CSRF validation, etc.)
     * - Throw an exception if validation fails
     * - Return void to allow the request to proceed
     * 
     * @return void
     * @throws \App\Exceptions\AppException If middleware check fails
     */
    public function handle(): void;
}
