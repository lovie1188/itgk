<?php

/**
 * CsrfMiddleware - CSRF Token Validation Middleware
 * 
 * Validates CSRF tokens on state-changing requests (POST, PUT, DELETE, PATCH).
 * Protects against Cross-Site Request Forgery attacks.
 * 
 * @package App\Middleware
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Middleware\MiddlewareInterface;
use App\Helpers\Csrf;
use App\Exceptions\ValidationException;
use App\Helpers\Logger;

class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * HTTP methods that require CSRF validation
     * @var array
     */
    private array $protectedMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];

    /**
     * Routes to exclude from CSRF validation
     * @var array
     */
    private array $excludedRoutes = [];

    /**
     * Constructor
     * 
     * @param array $excludedRoutes Routes to exclude from validation
     */
    public function __construct(array $excludedRoutes = [])
    {
        $this->excludedRoutes = $excludedRoutes;
    }

    /**
     * Handle the middleware
     * 
     * Validates CSRF token for state-changing requests.
     * 
     * @return void
     * @throws ValidationException If CSRF token is invalid
     */
    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Only check for state-changing methods
        if (!in_array($method, $this->protectedMethods)) {
            return;
        }

        // Check if route is excluded
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        foreach ($this->excludedRoutes as $route) {
            if (strpos($uri, $route) !== false) {
                Logger::debug('CsrfMiddleware: Route excluded from CSRF check', ['route' => $uri]);
                return;
            }
        }

        // Get token from various sources
        $token = $this->getTokenFromRequest();

        // Validate token
        if (!Csrf::verify($token)) {
            Logger::warning('CsrfMiddleware: Invalid CSRF token', [
                'uri' => $uri,
                'method' => $method,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);

            throw new ValidationException(
                'CSRF token mismatch. Please refresh the page and try again.',
                ['csrf_token' => 'Invalid or expired token']
            );
        }

        Logger::debug('CsrfMiddleware: CSRF token validated');
    }

    /**
     * Get CSRF token from request
     * 
     * Checks POST data, headers, and JSON body.
     * 
     * @return string|null
     */
    private function getTokenFromRequest(): ?string
    {
        // Check POST data
        if (isset($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }

        // Check headers
        $headers = [
            'HTTP_X_CSRF_TOKEN',
            'HTTP_X_XSRF_TOKEN',
            'HTTP_CSRF_TOKEN'
        ];

        foreach ($headers as $header) {
            if (isset($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }

        // Check JSON body
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $content = file_get_contents('php://input');
            $data = json_decode($content, true);

            if (isset($data['csrf_token'])) {
                return $data['csrf_token'];
            }
            if (isset($data['_token'])) {
                return $data['_token'];
            }
        }

        return null;
    }

    /**
     * Add route to exclusion list
     * 
     * @param string $route Route pattern to exclude
     * @return self
     */
    public function exclude(string $route): self
    {
        $this->excludedRoutes[] = $route;
        return $this;
    }
}
