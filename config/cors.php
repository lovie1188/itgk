<?php
// CORS / Security Header Constants
// Include this file at the top of index.php to apply headers on every request.

if (!defined('ALLOWED_ORIGIN')) {
    // Allow all origins in local/dev; override via env var ALLOWED_ORIGIN for production
    define('ALLOWED_ORIGIN', getenv('ALLOWED_ORIGIN') ?: '*');
}

if (!defined('CSP_NONCE')) {
    define('CSP_NONCE', bin2hex(random_bytes(16)));
}

// ── CORS headers ──────────────────────────────────────────────────────────────
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin) {
    header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token, X-XSRF-Token');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 86400');
}

// Handle preflight OPTIONS request — stop here after sending headers
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Security headers ─────────────────────────────────────────────────────────
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
