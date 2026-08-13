<?php

/**
 * BaseController - Base Controller for Web Controllers
 * 
 * Provides common functionality for all web controllers including
 * view rendering, JSON responses, and redirects.
 * 
 * @package App\Controllers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\View;
use App\Helpers\Logger;
use App\Helpers\Csrf;
use App\Services\AuthService;

/**
 * BaseController Class
 * 
 * Base class for all web controllers providing shared functionality.
 */
class BaseController
{
    /**
     * Constructor - Initialize session if not already started
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * View renderer instance
     * @var View|null
     */
    private ?View $viewRenderer = null;

    /**
     * Get view renderer instance
     * 
     * @return View
     */
    private function getViewRenderer(): View
    {
        if ($this->viewRenderer === null) {
            $this->viewRenderer = new View();
        }
        return $this->viewRenderer;
    }

    /**
     * Render a view with optional layout
     * 
     * @param string $path View path relative to Views directory
     * @param array $data Data to pass to the view
     * @param string|false $layout Layout template to use (false = no layout)
     * @return void
     */
    protected function view(string $path, array $data = [], string|false $layout = 'main'): void
    {
        // Add global data like user session, role, etc.
        $data['user'] = $_SESSION['user'] ?? null;
        $data['role'] = $_SESSION['role'] ?? 'GUEST';
        $data['name'] = $_SESSION['name'] ?? ($data['user']['name'] ?? 'Guest');

        $this->getViewRenderer()->render($path, $data, $layout);
    }

    /**
     * Return JSON response
     * 
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code (default 200)
     * @return void
     */
    protected function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to URL
     * 
     * @param string $url URL or path to redirect to
     * @return void
     */
    protected function redirect(string $url): void
    {
        // If external URL, redirect directly
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            header("Location: " . $url);
            exit;
        }

        // Prepend BASE_URL if relative or leading-slash path
        $baseUrl = defined('BASE_URL') ? BASE_URL : '/certificate/';
        if (strpos($url, $baseUrl) !== 0) {
            $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
        }

        header("Location: " . $url);
        exit;
    }

    /**
     * Make an external API request using cURL
     * 
     * @param string $url API endpoint URL
     * @param array $options Additional cURL options
     * @return string Response body
     * @throws \Exception On request failure
     */
    protected function makeApiRequest(string $url, array $options = []): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout'] ?? 30);

        // Disable SSL verification for local dev
        if (getenv('APP_ENV') !== 'production') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Apply additional options
        foreach ($options as $option => $value) {
            if ($option !== 'timeout') {
                curl_setopt($ch, $option, $value);
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        // Note: curl_close() is deprecated in PHP 8.3+ and handled automatically on cleanup

        if ($response === false) {
            Logger::error('API request failed', ['url' => $url, 'error' => $error]);
            throw new \Exception("cURL Error: $error");
        }

        if ($httpCode !== 200) {
            $errorMsg = $this->parseApiError($response, $httpCode);
            Logger::error('API returned error', ['url' => $url, 'http_code' => $httpCode, 'error' => $errorMsg]);
            throw new \Exception($errorMsg);
        }

        return $response;
    }

    /**
     * Parse API error response
     * 
     * @param string $response Response body
     * @param int $httpCode HTTP status code
     * @return string Parsed error message
     */
    private function parseApiError(string $response, int $httpCode): string
    {
        $errorMsg = "HTTP Error: $httpCode";
        $json = json_decode($response, true);

        if (!$json) {
            return $errorMsg;
        }

        $rawError = json_encode($json);

        // Check for specific known errors anywhere in the response
        if (strpos($rawError, 'Unable to parse range') !== false) {
            return "Sheet Name not found. Please verify the tab name matches exactly (e.g., Check for spaces, 'Sheet1' vs 'Sheet 1').";
        }

        // Extract best available message
        if (isset($json['error'])) {
            if (is_array($json['error'])) {
                if (isset($json['error']['message'])) {
                    $errorMsg = $json['error']['message'];
                } elseif (isset($json['error']['error'])) {
                    $errorMsg = $json['error']['error'];
                } else {
                    $errorMsg = json_encode($json['error']);
                }
            } else {
                $errorMsg = $json['error'];
            }
        } elseif (isset($json['message'])) {
            $errorMsg = $json['message'];
        } else {
            $errorMsg = "API Error: " . $rawError;
        }

        return $errorMsg;
    }

    /**
     * Check if user is authenticated
     * 
     * Uses $_SESSION['user_id'] (same key as AuthService::check())
     * to avoid auth state mismatch between BaseController and AuthMiddleware.
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Require authentication, redirect to login if not authenticated
     * 
     * @return void
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            if ($this->isAjaxRequest()) {
                $this->json(['success' => false, 'error' => 'Authentication required'], 401);
            }
            $this->redirect('login');
        }
    }

    /**
     * Check if current request is AJAX
     * 
     * @return bool
     */
    protected function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get current user from session
     * 
     * @return array|null
     */
    protected function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Get current user's role
     * 
     * @return string
     */
    protected function getCurrentRole(): string
    {
        return $_SESSION['role'] ?? 'GUEST';
    }

    /**
     * Check if current user has required role
     * 
     * @param string ...$roles Required roles
     * @return bool
     */
    protected function hasRole(string ...$roles): bool
    {
        $currentRole = $this->getCurrentRole();
        return in_array($currentRole, $roles);
    }

    /**
     * Require specific role(s), deny access if not authorized
     * 
     * @param string ...$roles Required roles
     * @return void
     */
    protected function requireRole(string ...$roles): void
    {
        if (!$this->hasRole(...$roles)) {
            if ($this->isAjaxRequest()) {
                $this->json(['success' => false, 'error' => 'Access denied'], 403);
            }
            http_response_code(403);
            echo 'Access Denied. You do not have permission to access this resource.';
            exit;
        }
    }

    /**
     * Require minimum role level, deny access if not authorized
     * 
     * @param string $role Minimum required role level
     * @return void
     */
    protected function requireRoleLevel(string $role): void
    {
        if (!AuthService::hasRoleLevel($role)) {
            if ($this->isAjaxRequest()) {
                $this->json(['success' => false, 'error' => 'Access denied'], 403);
            }
            http_response_code(403);
            echo 'Access Denied. You do not have permission to access this resource.';
            exit;
        }
    }

    /**
     * Validate CSRF token from request
     */
    protected function validateCsrf(): void
    {
        Csrf::validate();
    }

    /**
     * Check CSRF token status without throwing exception or exiting
     */
    protected function verifyCsrf(): bool
    {
        return Csrf::verify();
    }
}
