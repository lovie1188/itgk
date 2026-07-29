<?php

/**
 * Controller - Base Controller with Auth Methods
 * 
 * Provides common functionality for all controllers including:
 * - Authentication helpers
 * - View rendering
 * - JSON responses
 * - Redirects
 * - Input handling
 * 
 * @package App\Controllers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Helpers\View;
use App\Helpers\Csrf;
use App\Exceptions\ValidationException;

abstract class Controller
{
    /**
     * View renderer instance
     * @var View
     */
    protected View $view;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->view = new View();
    }
    
    // ==========================================
    // Authentication Methods
    // ==========================================

    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        return AuthService::check();
    }

    /**
     * Require authentication (redirect if not)
     * 
     * @return void
     */
    protected function requireAuth(): void
    {
        if (!AuthService::check()) {
            $this->redirect('/login');
        }
    }

    /**
     * Require specific role
     * 
     * @param string ...$roles Required roles
     * @return void
     */
    protected function requireRole(string ...$roles): void
    {
        AuthService::requireRole(...$roles);
    }

    /**
     * Require minimum role level
     * 
     * @param string $role Minimum role required
     * @return void
     */
    protected function requireRoleLevel(string $role): void
    {
        AuthService::requireRoleLevel($role);
    }

    /**
     * Require specific permission
     * 
     * @param string $permission Required permission
     * @return void
     */
    protected function requirePermission(string $permission): void
    {
        AuthService::requirePermission($permission);
    }

    /**
     * Check if user has role (returns bool)
     * 
     * @param string ...$roles Roles to check
     * @return bool
     */
    protected function hasRole(string ...$roles): bool
    {
        return AuthService::hasRole(...$roles);
    }

    /**
     * Check if user has permission (returns bool)
     * 
     * @param string $permission Permission to check
     * @return bool
     */
    protected function can(string $permission): bool
    {
        return AuthService::can($permission);
    }

    /**
     * Get current user
     * 
     * @return array|null
     */
    protected function user(): ?array
    {
        return AuthService::user();
    }

    /**
     * Get current user ID
     * 
     * @return int|null
     */
    protected function userId(): ?int
    {
        return AuthService::id();
    }

    /**
     * Get current user role
     * 
     * @return string
     */
    protected function userRole(): string
    {
        return AuthService::role();
    }

    /**
     * Check if user is SUPERADMIN
     * 
     * @return bool
     */
    protected function isSuperAdmin(): bool
    {
        return AuthService::isSuperAdmin();
    }

    /**
     * Check if user is ADMIN or higher
     * 
     * @return bool
     */
    protected function isAdmin(): bool
    {
        return AuthService::isAdmin();
    }
    
    // ==========================================
    // Response Methods
    // ==========================================

    /**
     * Render a view
     * 
     * @param string $template Template path
     * @param array $data Data to pass to view
     * @return void
     */
    protected function view(string $template, array $data = []): void
    {
        // Add common data
        $data['user'] = AuthService::user();
        $data['role'] = AuthService::role();
        $data['csrf_token'] = Csrf::getToken();

        $this->view->render($template, $data);
    }

    /**
     * Return JSON response
     * 
     * @param mixed $data Response data
     * @param int $status HTTP status code
     * @return void
     */
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');

        echo json_encode([
            'success' => $status >= 200 && $status < 300,
            'data' => $data,
            'timestamp' => date('c')
        ], JSON_PRETTY_PRINT);

        exit;
    }

    /**
     * Return JSON error response
     * 
     * @param string $message Error message
     * @param int $status HTTP status code
     * @param array $errors Additional errors
     * @return void
     */
    protected function jsonError(string $message, int $status = 400, array $errors = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $status
            ],
            'timestamp' => date('c')
        ];

        if (!empty($errors)) {
            $response['error']['errors'] = $errors;
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Return JSON success response
     * 
     * @param string $message Success message
     * @param mixed $data Additional data
     * @return void
     */
    protected function jsonSuccess(string $message, mixed $data = null): void
    {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('c')
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Redirect to URL
     * 
     * @param string $url URL or path
     * @param int $status HTTP status code (default 302)
     * @return void
     */
    protected function redirect(string $url, int $status = 302): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            http_response_code($status);
            header("Location: {$url}");
            exit;
        }

        $baseUrl = defined('BASE_URL') ? BASE_URL : '/certificate/';
        if (strpos($url, $baseUrl) !== 0) {
            $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
        }

        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    /**
     * Redirect back to previous page
     * 
     * @return void
     */
    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
        $this->redirect($referer);
    }
    
    // ==========================================
    // Input Methods
    // ==========================================

    /**
     * Get all input data
     * 
     * @return array
     */
    protected function input(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'GET') {
            return $_GET;
        }

        // Merge POST and JSON body
        $data = $_POST;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $json = json_decode(file_get_contents('php://input'), true) ?? [];
            $data = array_merge($data, $json);
        }

        return $data;
    }

    /**
     * Get specific input value
     * 
     * @param string $key Input key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function getInput(string $key, mixed $default = null): mixed
    {
        return $this->input()[$key] ?? $default;
    }

    /**
     * Validate input data
     * 
     * @param array $rules Validation rules
     * @return array Validated data
     * @throws ValidationException If validation fails
     */
    protected function validate(array $rules): array
    {
        $data = $this->input();
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;

            foreach ($fieldRules as $rule) {
                $error = $this->validateField($field, $value, $rule);
                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }

            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }

        if (!empty($errors)) {
            throw new ValidationException('Validation failed', $errors);
        }

        return $validated;
    }

    /**
     * Validate single field
     * 
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Validation rule
     * @return string|null Error message or null if valid
     */
    private function validateField(string $field, mixed $value, string $rule): ?string
    {
        // Parse rule and parameters
        $params = [];
        if (strpos($rule, ':') !== false) {
            [$rule, $paramStr] = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }

        return match ($rule) {
            'required' => ($value === null || $value === '')
                ? "The {$field} field is required."
                : null,
            'email' => ($value && !filter_var($value, FILTER_VALIDATE_EMAIL))
                ? "The {$field} must be a valid email address."
                : null,
            'min' => ($value && strlen((string)$value) < (int)$params[0])
                ? "The {$field} must be at least {$params[0]} characters."
                : null,
            'max' => ($value && strlen((string)$value) > (int)$params[0])
                ? "The {$field} may not be greater than {$params[0]} characters."
                : null,
            'numeric' => ($value && !is_numeric($value))
                ? "The {$field} must be a number."
                : null,
            'integer' => ($value && !filter_var($value, FILTER_VALIDATE_INT))
                ? "The {$field} must be an integer."
                : null,
            'in' => ($value && !in_array($value, $params))
                ? "The selected {$field} is invalid."
                : null,
            default => null
        };
    }
    
    // ==========================================
    // CSRF Methods
    // ==========================================

    /**
     * Validate CSRF token
     * 
     * @return void
     * @throws ValidationException If CSRF token is invalid
     */
    protected function validateCsrf(): void
    {
        Csrf::validate();
    }

    /**
     * Get CSRF token
     * 
     * @return string
     */
    protected function csrfToken(): string
    {
        return Csrf::getToken();
    }

    /**
     * Regenerate CSRF token
     * 
     * @return string New token
     */
    protected function regenerateCsrf(): string
    {
        return Csrf::regenerate();
    }
    
    // ==========================================
    // Session Methods
    // ==========================================

    /**
     * Flash message to session
     * 
     * @param string $key Message key
     * @param mixed $value Message value
     * @return void
     */
    protected function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Get flashed message
     * 
     * @param string $key Message key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Check if flash message exists
     * 
     * @param string $key Message key
     * @return bool
     */
    protected function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }
}
