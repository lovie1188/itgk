<?php

/**
 * Csrf - Cross-Site Request Forgery Protection
 * 
 * Provides CSRF token generation and validation for form submissions.
 * 
 * @package App\Helpers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Helpers;

class Csrf
{
    /**
     * Token name in session
     * @var string
     */
    private static string $tokenName = 'csrf_token';

    /**
     * Token lifetime in seconds (default: 2 hours)
     * @var int
     */
    private static int $tokenLifetime = 7200;

    /**
     * Generate a new CSRF token and store it in the session
     * 
     * @return string The generated token
     */
    public static function generate(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate a new token
        $token = bin2hex(random_bytes(32));

        // Store token with timestamp
        $_SESSION[self::$tokenName] = [
            'value' => $token,
            'time' => time()
        ];

        return $token;
    }

    /**
     * Get the current CSRF token (generate if not exists)
     * 
     * @return string|null
     */
    public static function getToken(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Return existing token if valid
        if (isset($_SESSION[self::$tokenName])) {
            $tokenData = $_SESSION[self::$tokenName];

            // Check if token has expired
            if (time() - $tokenData['time'] < self::$tokenLifetime) {
                return $tokenData['value'];
            }
        }

        // Generate new token
        return self::generate();
    }

    /**
     * Verify the CSRF token from the request
     * 
     * @param string|null $token The token to verify (if null, gets from request)
     * @return bool True if valid, false otherwise
     */
    public static function verify(?string $token = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Get token from request if not provided
        if ($token === null) {
            $token = $_POST['csrf_token']
                ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? null;
        }

        // Check if token exists in session
        if (!isset($_SESSION[self::$tokenName]) || empty($token)) {
            return false;
        }

        $tokenData = $_SESSION[self::$tokenName];

        // Check if token has expired
        if (time() - $tokenData['time'] > self::$tokenLifetime) {
            self::clear();
            return false;
        }

        // Use timing-safe comparison
        return hash_equals($tokenData['value'], $token);
    }

    /**
     * Validate token and throw exception if invalid
     * 
     * @param string|null $token The token to validate
     * @return void
     * @throws \App\Exceptions\ValidationException If token is invalid
     */
    public static function validate(?string $token = null): void
    {
        if (!self::verify($token)) {
            throw new \App\Exceptions\ValidationException(
                'CSRF token mismatch. Please refresh the page and try again.',
                ['csrf_token' => 'Invalid or expired token']
            );
        }
    }

    /**
     * Clear the CSRF token from session
     * 
     * @return void
     */
    public static function clear(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION[self::$tokenName]);
    }

    /**
     * Regenerate the CSRF token (useful after form submission)
     * 
     * @return string The new token
     */
    public static function regenerate(): string
    {
        self::clear();
        return self::generate();
    }

    /**
     * Output a hidden input field with the CSRF token
     * 
     * @param string $fieldName Field name (default: csrf_token)
     * @return void
     */
    public static function field(string $fieldName = 'csrf_token'): void
    {
        $token = self::getToken();
        echo '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Get HTML string for CSRF field
     * 
     * @param string $fieldName Field name
     * @return string HTML input element
     */
    public static function fieldHtml(string $fieldName = 'csrf_token'): string
    {
        $token = self::getToken();
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($fieldName),
            htmlspecialchars($token)
        );
    }

    /**
     * Get meta tag for CSRF token (for AJAX requests)
     * 
     * @return string HTML meta element
     */
    public static function metaTag(): string
    {
        $token = self::getToken();
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            htmlspecialchars($token)
        );
    }

    /**
     * Get token for JavaScript usage
     * 
     * @return string
     */
    public static function forJs(): string
    {
        return self::getToken();
    }

    /**
     * Set token lifetime
     * 
     * @param int $seconds Lifetime in seconds
     * @return void
     */
    public static function setTokenLifetime(int $seconds): void
    {
        self::$tokenLifetime = $seconds;
    }

    /**
     * Check if token exists in session
     * 
     * @return bool
     */
    public static function exists(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION[self::$tokenName]);
    }
}
