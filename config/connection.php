<?php
// File: config/connection.php

// Load Env
require_once __DIR__ . '/../app/Helpers/Env.php';
\App\Helpers\Env::load(__DIR__ . '/../.env');

// Autoload Helpers
require_once __DIR__ . '/../app/Helpers/View.php';
require_once __DIR__ . '/../app/Helpers/Csrf.php';

// Prevent any output in production
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Base URL for the project
if (!defined('BASE_URL')) {
    $_cu = getenv('BASE_URL');
    if (!$_cu || !is_string($_cu)) $_cu = '/';
    define('BASE_URL', $_cu);
}

// SSO Configuration
if (!defined('SSO_URL')) {
    define('SSO_URL', getenv('SSO_URL') ?: 'http://localhost/softtechsso');
}

// Database configuration
$servername = getenv('DB_HOST') ?: "localhost";
$username = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASS') ?: "";
$dbname = getenv('DB_NAME') ?: "soft_sam";

try {
    // Create PDO connection with proper charset and error handling
    $conn = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    // In production, show generic message
    if (getenv('APP_ENV') === 'production') {
         die("Database connection failed");
    } else {
         throw new Exception("Database connection failed: " . $e->getMessage());
    }
}

// Logging function
function logError($message, $context = [])
{
    $logEntry = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $logEntry .= " - Context: " . json_encode($context);
    }
    error_log($logEntry);
}

// Logging function
function logSuccess($message, $context = [])
{
    $logEntry = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) {
        $logEntry .= " - Context: " . json_encode($context);
    }
    error_log($logEntry);
}
