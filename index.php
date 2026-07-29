<?php

/**
 * Index.php - Application Entry Point
 * 
 * Main entry point for the SoftSam Portal application.
 * Initializes the application and routes requests.
 * 
 * @package SoftSam Portal
 * @author SoftTech Team
 */

declare(strict_types=1);

// ==========================================
// 1. Bootstrap Application
// ==========================================

// Load environment variables first
require_once __DIR__ . '/app/Helpers/Env.php';
App\Helpers\Env::load(__DIR__ . '/.env');

// Define constants
if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('BASE_URL') ?: '/certificate/');
}

// Configure error reporting
$appEnv = getenv('APP_ENV') ?: 'development';
if ($appEnv === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Start session - set cookie path to always match BASE_URL
$sessionCookiePath = BASE_URL;
session_set_cookie_params([
    'lifetime' => (int)(getenv('SESSION_LIFETIME') ?: 7200),
    'path' => $sessionCookiePath,
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Apply CORS and security headers on every request
require_once __DIR__ . '/config/cors.php';

// ==========================================
// 2. Load Autoloader
// ==========================================

// Load Composer autoloader if available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Fallback: Load files manually
    require_once __DIR__ . '/app/Core/ErrorHandler.php';
    require_once __DIR__ . '/app/Core/Router.php';
    require_once __DIR__ . '/app/Core/Database.php';
    require_once __DIR__ . '/app/Helpers/Logger.php';
    require_once __DIR__ . '/app/Helpers/View.php';
    require_once __DIR__ . '/app/Helpers/Csrf.php';
    require_once __DIR__ . '/app/Services/AuthService.php';
}

// Register error handler
App\Core\ErrorHandler::register();

// ==========================================
// 3. Initialize Router
// ==========================================

$router = new App\Core\Router();

// ==========================================
// 4. Define Web Routes
// ==========================================

// Public routes (no authentication required)
$router->get('/login', 'App\\Controllers\\AuthController', 'login');
$router->get('/logout', 'App\\Controllers\\AuthController', 'logout');
$router->get('/auth/sso', 'App\\Controllers\\AuthController', 'ssoRedirect');
$router->get('/auth/callback', 'App\\Controllers\\AuthController', 'callback');
$router->post('/auth/firebase-verify', 'App\\Controllers\\AuthController', 'firebaseVerify');

// Legacy route support (for backward compatibility)
$router->get('/itgk_certificate.php', 'App\\Controllers\\CertificateController', 'index');
$router->get('/learner_result.php', 'App\\Controllers\\LearnerController', 'index');

// Public Verification routes (no authentication required)
$router->get('/verify/transaction', 'App\\Controllers\\CertificateController', 'verifyTransaction');
$router->post('/verify/log', 'App\\Controllers\\CertificateController', 'logVerification');

// Protected routes (authentication required)
$router->group([
    'middleware' => ['App\\Middleware\\AuthMiddleware']
], function (App\Core\Router $router) {

    // Dashboard - requires authentication
    $router->get('/', 'App\\Controllers\\DashboardController', 'index');
    $router->get('/dashboard', 'App\\Controllers\\DashboardController', 'index');

    // Profile
    $router->get('/profile', 'App\\Controllers\\ProfileController', 'index');
    $router->post('/profile/update', 'App\\Controllers\\ProfileController', 'update');

    // Analytics
    $router->get('/analytics', 'App\\Controllers\\AnalyticsController', 'index');

    // Certificates
    $router->get('/itgk/list', 'App\\Controllers\\CertificateController', 'index');
    $router->get('/certificates', 'App\\Controllers\\CertificateController', 'index');
    $router->get('/itgk/acknowledgement', 'App\\Controllers\\CertificateController', 'acknowledgement');
    $router->post('/itgk/send_ack_email', 'App\\Controllers\\CertificateController', 'sendAckEmail');

    // Learners
    $router->get('/learners/list', 'App\\Controllers\\LearnerController', 'index');
    $router->get('/learners', 'App\\Controllers\\LearnerController', 'index');
    $router->get('/learners/edit', 'App\\Controllers\\LearnerController', 'edit');
    $router->get('/learners/acknowledgement', 'App\\Controllers\\LearnerController', 'acknowledgement');

    // ==========================================
    // SUPERADMIN Routes
    // ==========================================
    $router->group([
        'middleware' => ['App\\Middleware\\RoleMiddleware:SUPERADMIN']
    ], function (App\Core\Router $router) {

        // Certificate management
        $router->post('/itgk/create', 'App\\Controllers\\CertificateController', 'store');
        $router->post('/itgk/update', 'App\\Controllers\\CertificateController', 'update');
        $router->post('/itgk/consolidate', 'App\\Controllers\\CertificateController', 'consolidate');
        $router->post('/itgk/issue_batch', 'App\\Controllers\\CertificateController', 'issueBatch');
        $router->post('/itgk/bulk_issue', 'App\\Controllers\\CertificateController', 'bulkIssue');
        $router->post('/itgk/delete', 'App\\Controllers\\CertificateController', 'delete');
        $router->post('/certificates/consolidate', 'App\\Controllers\\CertificateController', 'consolidate');

        // Learner management
        $router->post('/learners/create', 'App\\Controllers\\LearnerController', 'store');
        $router->post('/learners/update', 'App\\Controllers\\LearnerController', 'update');
        $router->post('/learners/delete', 'App\\Controllers\\LearnerController', 'delete');
        $router->post('/learners/issue', 'App\\Controllers\\LearnerController', 'issue');
        $router->post('/learners/issue_individual', 'App\\Controllers\\LearnerController', 'issueIndividual');

        // Upload
        $router->get('/data-upload', 'App\\Controllers\\UploadController', 'index');
        $router->post('/data-upload/table-schema', 'App\\Controllers\\UploadController', 'getTableSchema');
        $router->post('/data-upload/fetch-sheet', 'App\\Controllers\\UploadController', 'fetchGoogleSheet');
        $router->post('/data-upload/file', 'App\\Controllers\\UploadController', 'uploadFile');
        $router->post('/data-upload/perform', 'App\\Controllers\\UploadController', 'performUpload');

        // Setup
        $router->get('/setup', 'App\\Controllers\\SetupController', 'index');
        $router->post('/setup/save', 'App\\Controllers\\SetupController', 'save');
        $router->post('/setup/test-connection', 'App\\Controllers\\SetupController', 'testConnection');

        // SMTP Email Setup
        $router->get('/smtp-setup', 'App\\Controllers\\SmtpController', 'index');
        $router->post('/smtp-setup/save', 'App\\Controllers\\SmtpController', 'save');
        $router->post('/smtp-setup/test', 'App\\Controllers\\SmtpController', 'test');
    });
});

// ==========================================
// 5. Load API Routes
// ==========================================

$apiRoutes = require __DIR__ . '/routes/api.php';

// ==========================================
// 6. Dispatch Request
// ==========================================

// Get request URI and method
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Debug: Log the incoming request
if (getenv('APP_DEBUG') === 'true') {
    App\Helpers\Logger::debug('Incoming request', [
        'uri' => $uri,
        'method' => $method,
        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'not set',
        'base_url' => BASE_URL
    ]);
}

// Handle API requests separately
if (strpos($uri, '/api/') !== false) {
    // Load API routes and dispatch
    $router = $apiRoutes;
}

// Debug logging
if (getenv('APP_DEBUG') === 'true') {
    App\Helpers\Logger::debug('Request received', [
        'uri' => $uri,
        'method' => $method,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ]);
}

// Dispatch - let the Router handle URI normalization
try {
    $router->dispatch($uri, $method);
} catch (App\Exceptions\NotFoundException $e) {
    http_response_code(404);

    if (strpos($uri, '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => 'Route not found',
                'code' => 404
            ]
        ]);
    } else {
        require __DIR__ . '/app/Views/pages/errors/404.php';
    }
} catch (App\Exceptions\AuthException $e) {
    // Handle authentication exceptions - redirect to login
    App\Helpers\Logger::info('Auth exception, redirecting to login', [
        'message' => $e->getMessage()
    ]);

    // For AJAX requests, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => 'Authentication required',
                'code' => 401
            ]
        ]);
    } else {
        // For regular requests, redirect to login
        header('Location: ' . BASE_URL . 'login');
    }
} catch (Exception $e) {
    App\Helpers\Logger::error('Unhandled exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    http_response_code(500);

    if (strpos($uri, '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => getenv('APP_DEBUG') === 'true' ? $e->getMessage() : 'Internal server error',
                'code' => 500
            ]
        ]);
    } else {
        echo '<h1>500 Internal Server Error</h1>';
        if (getenv('APP_DEBUG') === 'true') {
            echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        }
    }
}
