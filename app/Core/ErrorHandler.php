<?php

/**
 * ErrorHandler - Global Error and Exception Handler
 * 
 * Provides centralized error handling for the entire application.
 * Converts errors to exceptions, logs all errors, and renders
 * appropriate responses for API and web requests.
 * 
 * @package App\Core
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Helpers\Logger;
use Throwable;

class ErrorHandler
{
    /**
     * Register all error handlers
     * 
     * @return void
     */
    public static function register(): void
    {
        // Set error handler to convert errors to exceptions
        set_error_handler([self::class, 'handleError']);

        // Set exception handler
        set_exception_handler([self::class, 'handleException']);

        // Set shutdown function for fatal errors
        register_shutdown_function([self::class, 'handleShutdown']);

        // Report all errors
        error_reporting(E_ALL);
    }

    /**
     * Handle PHP errors by converting to exceptions
     * 
     * @param int $level Error level
     * @param string $message Error message
     * @param string $file File where error occurred
     * @param int $line Line number
     * @return bool Never returns (throws exception)
     * @throws \ErrorException
     */
    public static function handleError(
        int $level,
        string $message,
        string $file = '',
        int $line = 0
    ): bool {
        // Don't throw for suppressed errors
        if (!(error_reporting() & $level)) {
            return false;
        }

        // Convert error to ErrorException
        throw new \ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * Handle uncaught exceptions
     * 
     * @param Throwable $exception The exception
     * @return void
     */
    public static function handleException(Throwable $exception): void
    {
        // Log the exception
        self::logException($exception);

        // Determine if API or Web request
        $isApi = self::isApiRequest();

        // Send appropriate response
        if ($isApi) {
            self::renderApiError($exception);
        } else {
            self::renderWebError($exception);
        }
    }

    /**
     * Handle shutdown errors (fatal errors)
     * 
     * @return void
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error !== null && self::isFatalError($error['type'])) {
            // Convert to ErrorException and handle
            $exception = new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );

            self::handleException($exception);
        }
    }

    /**
     * Log exception to file and console
     * 
     * @param Throwable $exception The exception
     * @return void
     */
    private static function logException(Throwable $exception): void
    {
        $context = [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ];

        // Add context from AppException
        if ($exception instanceof AppException) {
            $context = array_merge($context, $exception->getContext());
        }

        // Log based on severity
        if ($exception instanceof AppException) {
            $statusCode = $exception->getStatusCode();
            if ($statusCode >= 500) {
                Logger::error($exception->getMessage(), $context);
            } elseif ($statusCode >= 400) {
                Logger::warning($exception->getMessage(), $context);
            } else {
                Logger::info($exception->getMessage(), $context);
            }
        } else {
            Logger::error($exception->getMessage(), $context);
        }
    }

    /**
     * Render error for API requests
     * 
     * @param Throwable $exception The exception
     * @return void
     */
    private static function renderApiError(Throwable $exception): void
    {
        // Get status code
        $statusCode = $exception instanceof AppException
            ? $exception->getStatusCode()
            : 500;

        // Set HTTP status code
        http_response_code($statusCode);

        // Set headers
        header('Content-Type: application/json');
        header('X-API-Version: 1.0');

        // Build response
        if ($exception instanceof AppException) {
            $response = $exception->toArray(self::isDebugMode());
        } else {
            $response = [
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => self::isDebugMode()
                        ? $exception->getMessage()
                        : 'An internal error occurred',
                    'status' => $statusCode
                ],
                'timestamp' => date('c')
            ];

            if (self::isDebugMode()) {
                $response['debug'] = [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => array_slice(explode("\n", $exception->getTraceAsString()), 0, 10)
                ];
            }
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Render error for web requests
     * 
     * @param Throwable $exception The exception
     * @return void
     */
    private static function renderWebError(Throwable $exception): void
    {
        // Get status code
        $statusCode = $exception instanceof AppException
            ? $exception->getStatusCode()
            : 500;

        // Set HTTP status code
        http_response_code($statusCode);

        // Try to render error page
        $errorViewPath = __DIR__ . '/../Views/pages/errors/' . $statusCode . '.php';

        if (file_exists($errorViewPath)) {
            // Extract variables for the view
            $message = $exception->getMessage();
            $debug = self::isDebugMode() ? [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ] : null;

            require $errorViewPath;
        } else {
            // Render default error page
            echo self::getDefaultErrorPage($statusCode, $exception->getMessage());
        }

        exit;
    }

    /**
     * Get default HTML error page
     * 
     * @param int $code HTTP status code
     * @param string $message Error message
     * @return string HTML content
     */
    private static function getDefaultErrorPage(int $code, string $message): string
    {
        $title = self::getErrorTitle($code);
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error {$code} - SoftSam Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            background: #fff;
            border-radius: 12px;
            padding: 40px;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: #e74c3c;
            line-height: 1;
            margin-bottom: 10px;
        }
        .error-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 15px;
        }
        .error-message {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn-home {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            color: #fff;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">{$code}</div>
        <h1 class="error-title">{$title}</h1>
        <p class="error-message">{$safeMessage}</p>
        <a href="/" class="btn-home">Go to Dashboard</a>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Get error title based on status code
     * 
     * @param int $code HTTP status code
     * @return string Error title
     */
    private static function getErrorTitle(int $code): string
    {
        return match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Access Forbidden',
            404 => 'Page Not Found',
            419 => 'Session Expired',
            422 => 'Validation Error',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            default => 'Error'
        };
    }

    /**
     * Check if current request is an API request
     * 
     * @return bool
     */
    private static function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';

        return strpos($uri, '/api/') !== false
            || str_contains($accept, 'application/json')
            || strtolower($requestedWith) === 'xmlhttprequest';
    }

    /**
     * Check if debug mode is enabled
     * 
     * @return bool
     */
    private static function isDebugMode(): bool
    {
        return getenv('APP_DEBUG') === 'true';
    }

    /**
     * Check if error type is fatal
     * 
     * @param int $type Error type
     * @return bool
     */
    private static function isFatalError(int $type): bool
    {
        return in_array($type, [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR
        ]);
    }
}
