<?php

/**
 * AuthMiddleware - Authentication Check Middleware
 * 
 * Verifies that the user is authenticated before allowing access.
 * Should be used on all protected routes.
 * 
 * @package App\Middleware
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Middleware\MiddlewareInterface;
use App\Services\AuthService;
use App\Exceptions\AuthException;
use App\Helpers\Logger;

class AuthMiddleware implements MiddlewareInterface
{
    /**
     * Handle the middleware
     * 
     * Checks if user is authenticated and session is valid.
     * 
     * @return void
     * @throws AuthException If not authenticated
     */
    public function handle(): void
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is authenticated
        if (!AuthService::check()) {
            Logger::debug('AuthMiddleware: User not authenticated', [
                'uri' => $_SERVER['REQUEST_URI'] ?? ''
            ]);

            throw AuthException::notAuthenticated();
        }

        // Check if session has expired
        if (AuthService::isSessionExpired()) {
            Logger::info('AuthMiddleware: Session expired', [
                'user_id' => AuthService::id()
            ]);

            AuthService::logout();
            throw AuthException::sessionExpired();
        }

        // Update last activity
        AuthService::updateActivity();

        // Regenerate session ID periodically for security
        $this->regenerateSessionIfNeeded();
    }

    /**
     * Regenerate session ID periodically
     * 
     * @return void
     */
    private function regenerateSessionIfNeeded(): void
    {
        $regenerationInterval = 1800; // 30 minutes

        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
            return;
        }

        if (time() - $_SESSION['last_regeneration'] > $regenerationInterval) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();

            Logger::debug('AuthMiddleware: Session ID regenerated', [
                'user_id' => AuthService::id()
            ]);
        }
    }
}
