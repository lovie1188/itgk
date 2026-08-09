<?php

/**
 * Logger - Centralized Logging System
 * 
 * Provides PSR-3 style logging with file and console output support.
 * All critical events should be logged through this class.
 * 
 * @package App\Helpers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Helpers;

class Logger
{
    /**
     * Log file path
     * @var string
     */
    private static string $logFile = __DIR__ . '/../../storage/logs/app.log';

    /**
     * Debug mode flag
     * @var bool
     */
    private static bool $debugMode = false;

    /**
     * Whether logger has been initialized
     * @var bool
     */
    private static bool $initialized = false;

    /**
     * Log levels with their priorities
     */
    private const LEVELS = [
        'DEBUG'     => 0,
        'INFO'      => 1,
        'NOTICE'    => 2,
        'WARNING'   => 3,
        'ERROR'     => 4,
        'CRITICAL'  => 5,
        'ALERT'     => 6,
        'EMERGENCY' => 7
    ];

    /**
     * Minimum log level to record
     * @var int
     */
    private static int $minLevel = 0;

    /**
     * Initialize the logger
     * Called automatically on first log, or can be called explicitly
     * 
     * @return void
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        // Load debug mode from environment
        self::$debugMode = getenv('APP_DEBUG') === 'true';

        // Set minimum log level based on debug mode
        self::$minLevel = self::$debugMode ? 0 : 1; // DEBUG in debug mode, INFO otherwise

        // Ensure log directory exists
        $dir = dirname(self::$logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        self::$initialized = true;
    }

    /**
     * Log an informational message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Log an error message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Log a warning message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Alias for warning method
     */
    public static function warn(string $message, array $context = []): void
    {
        self::warning($message, $context);
    }

    /**
     * Log a debug message (only in debug mode)
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function debug(string $message, array $context = []): void
    {
        if (self::$debugMode) {
            self::log('DEBUG', $message, $context);
        }
    }

    /**
     * Log a notice message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function notice(string $message, array $context = []): void
    {
        self::log('NOTICE', $message, $context);
    }

    /**
     * Log a critical error message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log('CRITICAL', $message, $context);
    }

    /**
     * Log an alert message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function alert(string $message, array $context = []): void
    {
        self::log('ALERT', $message, $context);
    }

    /**
     * Log an emergency message
     * 
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function emergency(string $message, array $context = []): void
    {
        self::log('EMERGENCY', $message, $context);
    }

    /**
     * Core logging method
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        // Initialize if not already done
        self::init();

        // Check if this level should be logged
        $levelPriority = self::LEVELS[$level] ?? 0;
        if ($levelPriority < self::$minLevel) {
            return;
        }

        // Format timestamp
        $timestamp = date('Y-m-d H:i:s');

        // Add request context
        $context = self::addRequestContext($context);

        // Format context as JSON
        $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';

        // Build log line
        $logLine = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;

        // Write to file
        file_put_contents(self::$logFile, $logLine, FILE_APPEND | LOCK_EX);

        // Output to console in debug mode (except for DEBUG level to avoid recursion)
        if (self::$debugMode && $level !== 'DEBUG') {
            self::consoleLog($level, $message, $context);
        }
    }

    /**
     * Add request context to log entry
     * 
     * @param array $context Existing context
     * @return array Context with request info added
     */
    private static function addRequestContext(array $context): array
    {
        // Add request info if available
        if (isset($_SERVER['REQUEST_URI'])) {
            $context['_request'] = [
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ];
        }

        // Add user ID if logged in
        if (isset($_SESSION['user_id'])) {
            $context['_user_id'] = $_SESSION['user_id'];
        }

        return $context;
    }

    /**
     * Output log to console (error_log)
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Context data
     * @return void
     */
    private static function consoleLog(string $level, string $message, array $context): void
    {
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';
        error_log("[SoftSam] {$level}: {$message}{$contextStr}");
    }

    /**
     * Set custom log file path
     * 
     * @param string $path File path
     * @return void
     */
    public static function setLogFile(string $path): void
    {
        self::$logFile = $path;
        self::$initialized = false;
    }

    /**
     * Set debug mode
     * 
     * @param bool $debug Debug mode flag
     * @return void
     */
    public static function setDebugMode(bool $debug): void
    {
        self::$debugMode = $debug;
        self::$minLevel = $debug ? 0 : 1;
    }

    /**
     * Get current log file path
     * 
     * @return string
     */
    public static function getLogFile(): string
    {
        return self::$logFile;
    }

    /**
     * Clear log file
     * 
     * @return bool True on success
     */
    public static function clear(): bool
    {
        if (file_exists(self::$logFile)) {
            return file_put_contents(self::$logFile, '') !== false;
        }
        return true;
    }

    /**
     * Get log file contents
     * 
     * @param int $lines Number of lines to return (0 = all)
     * @return string Log contents
     */
    public static function getContents(int $lines = 100): string
    {
        if (!file_exists(self::$logFile)) {
            return '';
        }

        if ($lines === 0) {
            return file_get_contents(self::$logFile);
        }

        // Get last N lines
        $output = [];
        $file = fopen(self::$logFile, 'r');
        if ($file) {
            $lineBuffer = [];
            while (!feof($file)) {
                $line = fgets($file);
                if ($line !== false) {
                    $lineBuffer[] = $line;
                    if (count($lineBuffer) > $lines) {
                        array_shift($lineBuffer);
                    }
                }
            }
            fclose($file);
            $output = $lineBuffer;
        }

        return implode('', $output);
    }
}
