<?php

/**
 * RateLimitMiddleware - Rate Limiting Middleware
 * 
 * Limits the number of requests a client can make within a time window.
 * Uses file-based storage (can be replaced with Redis in production).
 * 
 * @package App\Middleware
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Middleware\MiddlewareInterface;
use App\Exceptions\RateLimitException;
use App\Helpers\Logger;

class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * Maximum number of attempts
     * @var int
     */
    private int $maxAttempts;

    /**
     * Time window in minutes
     * @var int
     */
    private int $decayMinutes;

    /**
     * Key prefix for storage
     * @var string
     */
    private string $keyPrefix;

    /**
     * Storage directory
     * @var string
     */
    private static string $storageDir;

    /**
     * Constructor
     * 
     * @param int $maxAttempts Maximum requests allowed
     * @param int $decayMinutes Time window in minutes
     * @param string|null $key Custom key prefix
     */
    public function __construct(
        int $maxAttempts = 60,
        int $decayMinutes = 1,
        ?string $key = null
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
        $this->keyPrefix = $key ?? 'rate_limit';

        // Set storage directory
        self::$storageDir = sys_get_temp_dir() . '/softsam_rate_limit';

        // Ensure storage directory exists
        if (!is_dir(self::$storageDir)) {
            mkdir(self::$storageDir, 0777, true);
        }
    }

    /**
     * Handle the middleware
     * 
     * @return void
     * @throws RateLimitException If rate limit exceeded
     */
    public function handle(): void
    {
        $key = $this->generateKey();
        $attempts = $this->getAttempts($key);

        // Check if limit exceeded
        if ($attempts >= $this->maxAttempts) {
            $retryAfter = $this->getRetryAfter($key);

            // Set rate limit headers
            header("Retry-After: {$retryAfter}");
            header("X-RateLimit-Limit: {$this->maxAttempts}");
            header("X-RateLimit-Remaining: 0");
            header("X-RateLimit-Reset: " . (time() + $retryAfter));

            Logger::warning('RateLimitMiddleware: Rate limit exceeded', [
                'key' => $key,
                'attempts' => $attempts,
                'max_attempts' => $this->maxAttempts,
                'retry_after' => $retryAfter,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);

            throw new RateLimitException(
                "Too many requests. Please try again in {$retryAfter} seconds.",
                $retryAfter,
                $this->maxAttempts
            );
        }

        // Increment attempts
        $this->incrementAttempts($key);

        // Set rate limit headers
        $remaining = max(0, $this->maxAttempts - $attempts - 1);
        header("X-RateLimit-Limit: {$this->maxAttempts}");
        header("X-RateLimit-Remaining: {$remaining}");

        Logger::debug('RateLimitMiddleware: Request allowed', [
            'key' => $key,
            'attempts' => $attempts + 1,
            'remaining' => $remaining
        ]);
    }

    /**
     * Generate unique key for the client
     * 
     * @return string
     */
    private function generateKey(): string
    {
        // Use IP address as default identifier
        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Add user ID if authenticated
        if (isset($_SESSION['user_id'])) {
            $identifier .= '_' . $_SESSION['user_id'];
        }

        // Add route info for per-route limiting
        $route = $_SERVER['REQUEST_URI'] ?? '';

        return md5($this->keyPrefix . ':' . $identifier . ':' . $route);
    }

    /**
     * Get current attempt count
     * 
     * @param string $key Storage key
     * @return int
     */
    private function getAttempts(string $key): int
    {
        $file = self::$storageDir . '/' . $key;

        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode(file_get_contents($file), true);

        if (!$data || !isset($data['expires']) || $data['expires'] < time()) {
            // Expired, reset
            unlink($file);
            return 0;
        }

        return $data['count'] ?? 0;
    }

    /**
     * Increment attempt count
     * 
     * @param string $key Storage key
     * @return void
     */
    private function incrementAttempts(string $key): void
    {
        $file = self::$storageDir . '/' . $key;

        $data = [
            'count' => $this->getAttempts($key) + 1,
            'expires' => time() + ($this->decayMinutes * 60)
        ];

        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    /**
     * Get seconds until rate limit resets
     * 
     * @param string $key Storage key
     * @return int
     */
    private function getRetryAfter(string $key): int
    {
        $file = self::$storageDir . '/' . $key;

        if (!file_exists($file)) {
            return $this->decayMinutes * 60;
        }

        $data = json_decode(file_get_contents($file), true);

        if (!$data || !isset($data['expires'])) {
            return $this->decayMinutes * 60;
        }

        return max(0, $data['expires'] - time());
    }

    /**
     * Clear rate limit for a key
     * 
     * @param string|null $key Key to clear (null = current client)
     * @return void
     */
    public static function clear(?string $key = null): void
    {
        if ($key === null) {
            // Clear for current client
            $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $pattern = self::$storageDir . '/' . md5('*:' . $identifier . ':*');
        } else {
            $pattern = self::$storageDir . '/' . $key;
        }

        // Find and delete matching files
        $files = glob($pattern);
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Clear all rate limits
     * 
     * @return void
     */
    public static function clearAll(): void
    {
        $files = glob(self::$storageDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Create strict rate limiter for authentication endpoints
     * 
     * @return self
     */
    public static function strict(): self
    {
        return new self(5, 15, 'auth'); // 5 requests per 15 minutes
    }

    /**
     * Create moderate rate limiter for API endpoints
     * 
     * @return self
     */
    public static function moderate(): self
    {
        return new self(100, 1, 'api'); // 100 requests per minute
    }

    /**
     * Create lenient rate limiter for general use
     * 
     * @return self
     */
    public static function lenient(): self
    {
        return new self(300, 1, 'general'); // 300 requests per minute
    }
}
