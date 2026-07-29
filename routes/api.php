<?php

/**
 * API Routes - RESTful API Route Definitions
 * 
 * Returns a router configured with all API endpoints.
 * 
 * @package Routes
 * @author SoftTech Team
 */

declare(strict_types=1);

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\RateLimitMiddleware;

$apiRouter = new Router();

// ==========================================
// Public API Routes (No Authentication)
// ==========================================

// Authentication endpoints with strict rate limiting
$apiRouter->post('/api/v1/auth/login', 'App\\Controllers\\Api\\AuthController', 'login');
$apiRouter->post('/api/v1/auth/logout', 'App\\Controllers\\Api\\AuthController', 'logout');
$apiRouter->post('/api/v1/auth/refresh', 'App\\Controllers\\Api\\AuthController', 'refresh');
$apiRouter->post('/api/v1/auth/firebase-verify', 'App\\Controllers\\AuthController', 'firebaseVerify');

// SSO endpoints
$apiRouter->get('/api/v1/auth/sso', 'App\\Controllers\\Api\\AuthController', 'ssoRedirect');
$apiRouter->get('/api/v1/auth/callback', 'App\\Controllers\\Api\\AuthController', 'callback');

// ==========================================
// Protected API Routes (Authentication Required)
// ==========================================
$apiRouter->group([
    'middleware' => [AuthMiddleware::class]
], function (Router $router) {

    // Current user
    $router->get('/api/v1/user', 'App\\Controllers\\Api\\UserController', 'current');
    $router->put('/api/v1/user', 'App\\Controllers\\Api\\UserController', 'update');

    // Dashboard / Analytics
    $router->get('/api/v1/dashboard', 'App\\Controllers\\Api\\DashboardController', 'index');
    $router->get('/api/v1/analytics', 'App\\Controllers\\Api\\AnalyticsController', 'index');

    // Certificates - Read operations
    $router->get('/api/v1/certificates', 'App\\Controllers\\Api\\CertificateController', 'index');

    // Learners - Read operations
    $router->get('/api/v1/learners', 'App\\Controllers\\Api\\LearnerController', 'index');

    // ==========================================
    // Admin Routes (SUPERADMIN Role Required)
    // ==========================================
    $router->group([
        'middleware' => [RoleMiddleware::class . ':SUPERADMIN']
    ], function (Router $router) {

        // Certificate management
        $router->post('/api/v1/certificates', 'App\\Controllers\\Api\\CertificateController', 'store');
        $router->post('/api/v1/certificates/consolidate', 'App\\Controllers\\Api\\CertificateController', 'consolidate');
        $router->post('/api/v1/certificates/{id}/issue', 'App\\Controllers\\Api\\CertificateController', 'issueBatch');

        // Learner management
        $router->post('/api/v1/learners', 'App\\Controllers\\Api\\LearnerController', 'store');
        $router->post('/api/v1/learners/batch-delete', 'App\\Controllers\\Api\\LearnerController', 'delete');
        $router->post('/api/v1/learners/{id}/issue', 'App\\Controllers\\Api\\LearnerController', 'issueIndividual');

        // Upload management (Api UploadController - to be implemented)
        $router->get('/api/v1/upload/tables', 'App\\Controllers\\UploadController', 'getTableSchema');
        $router->get('/api/v1/upload/templates', 'App\\Controllers\\UploadController', 'getTemplates');
        $router->post('/api/v1/upload/file', 'App\\Controllers\\UploadController', 'uploadFile');
        $router->post('/api/v1/upload/google-sheet', 'App\\Controllers\\UploadController', 'fetchGoogleSheet');
        $router->post('/api/v1/upload/perform', 'App\\Controllers\\UploadController', 'performUpload');
        $router->post('/api/v1/upload/templates', 'App\\Controllers\\UploadController', 'saveTemplate');

        // ITGK management
        $router->get('/api/v1/itgk', 'App\\Controllers\\CertificateController', 'index');
    });

    // ==========================================
    // Admin Routes (ADMIN Role Required)
    // ==========================================
    $router->group([
        'middleware' => [RoleMiddleware::class . ':ADMIN']
    ], function (Router $router) {
        // ADMIN-only routes can be added here
    });
});

return $apiRouter;
