<?php

/**
 * Bootstrap - Application Initialization
 * 
 * Initializes the new MVC architecture with:
 * - Error handling
 * - Session management
 * - Autoloading
 * - Routing
 * 
 * @package App
 * @author SoftTech Team
 */

declare(strict_types=1);

// Load environment variables
require_once __DIR__ . '/app/Helpers/Env.php';
App\Helpers\Env::load(__DIR__ . '/.env');

// Define constants
if (!defined('BASE_URL')) {
    // Detect BASE_URL: use '/' when index.php is running as router script (built-in dev server),
    // otherwise use the environment value or '/certificate/' for Apache/XAMPP
    if (!defined('BASE_URL')) {
        $baseUrl = getenv('BASE_URL');
        if (!$baseUrl || !is_string($baseUrl)) {
            // Running standalone (built-in server docroot is the certificate/ dir)
            $baseUrl = '/';
        }
        define('BASE_URL', $baseUrl);
    }

}

if (!defined('SSO_URL')) {
    define('SSO_URL', getenv('SSO_URL') ?: 'http://localhost/softtechsso');
}

// Configure error reporting based on environment
$appEnv = getenv('APP_ENV') ?: 'development';
$appDebug = getenv('APP_DEBUG') === 'true';

if ($appEnv === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Register global error handler
App\Core\ErrorHandler::register();

// Load Composer autoloader if available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Helper function to get the current authenticated user
 * 
 * @return array|null
 */
function auth(): ?array
{
    return App\Services\AuthService::user();
}

/**
 * Helper function to check if user is authenticated
 * 
 * @return bool
 */
function is_authenticated(): bool
{
    return App\Services\AuthService::check();
}

/**
 * Helper function to get current user's role
 * 
 * @return string
 */
function user_role(): string
{
    return App\Services\AuthService::role();
}

/**
 * Helper function to check if user has role
 * 
 * @param string ...$roles Roles to check
 * @return bool
 */
function has_role(string ...$roles): bool
{
    return App\Services\AuthService::hasRole(...$roles);
}

/**
 * Get asset URL with SSO fallback
 * 
 * @param string $path Asset path
 * @return string
 */
function asset_url(string $path): string
{
    return App\Services\SSOService::asset($path);
}
