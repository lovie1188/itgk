<?php
// Ensure BASE_URL is defined
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../app/Helpers/Env.php';
    App\Helpers\Env::load(__DIR__ . '/../.env');
    $_au = getenv('BASE_URL');
    if (!$_au || !is_string($_au)) $_au = '/';
    define('BASE_URL', $_au);
}
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Validate session
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login");
    exit;
}

// 2. (Deprecated stub — the real auth is handled by AuthMiddleware now)
// Kept so that config/auth.php doesn't fail to load when still referenced
// by legacy action scripts.
