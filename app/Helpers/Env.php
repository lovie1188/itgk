<?php

/**
 * Env - Environment Variable Loader
 * 
 * Loads environment variables from .env file into $_ENV and $_SERVER.
 * Supports comments and quoted values.
 * 
 * @package App\Helpers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Helpers;

class Env
{
    /**
     * Path to .env file
     * @var string|null
     */
    private static ?string $path = null;

    /**
     * Whether env has been loaded
     * @var bool
     */
    private static bool $loaded = false;

    /**
     * Load environment variables from .env file
     * 
     * @param string $path Path to .env file
     * @return void
     */
    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        if (!file_exists($path)) {
            return;
        }

        self::$path = $path;

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse line
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $name = trim($parts[0]);
            $value = trim($parts[1]);

            // Remove quotes
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }

            // Set environment variable
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * Get environment variable
     * 
     * @param string $key Variable name
     * @param mixed $default Default value
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        // Convert string booleans
        if (is_string($value)) {
            switch (strtolower($value)) {
                case 'true':
                case '(true)':
                    return true;
                case 'false':
                case '(false)':
                    return false;
                case 'null':
                case '(null)':
                    return null;
                case 'empty':
                case '(empty)':
                    return '';
            }
        }

        return $value;
    }

    /**
     * Set environment variable
     * 
     * @param string $key Variable name
     * @param mixed $value Variable value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        putenv(sprintf('%s=%s', $key, $value));
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    /**
     * Check if environment variable exists
     * 
     * @param string $key Variable name
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset($_ENV[$key]) || isset($_SERVER[$key]) || getenv($key) !== false;
    }

    /**
     * Check if running in production
     * 
     * @return bool
     */
    public static function isProduction(): bool
    {
        return self::get('APP_ENV') === 'production';
    }

    /**
     * Check if running in development
     * 
     * @return bool
     */
    public static function isDevelopment(): bool
    {
        return self::get('APP_ENV') === 'development' || !self::isProduction();
    }

    /**
     * Check if debug mode is enabled
     * 
     * @return bool
     */
    public static function isDebug(): bool
    {
        return self::get('APP_DEBUG') === 'true';
    }

    /**
     * Update or append key-value pairs in the .env file
     * 
     * @param array $data Key-value array of variables to update
     * @param string|null $envFilePath Optional .env path
     * @return bool
     */
    public static function updateEnvFile(array $data, ?string $envFilePath = null): bool
    {
        $path = $envFilePath ?? self::$path ?? dirname(__DIR__, 2) . '/.env';
        if (!file_exists($path) || !is_writable($path)) {
            return false;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return false;
        }

        foreach ($data as $key => $value) {
            $valueStr = (string)$value;
            // Quote string if it contains spaces
            if (preg_match('/\s/', $valueStr) && !preg_match('/^".*"$/', $valueStr)) {
                $valueStr = '"' . str_replace('"', '\"', $valueStr) . '"';
            }

            self::set($key, $valueStr);

            $pattern = "/^" . preg_quote($key, '/') . "=.*$/m";
            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$valueStr}", $content);
            } else {
                $content = rtrim($content) . "\n{$key}={$valueStr}\n";
            }
        }

        return file_put_contents($path, $content) !== false;
    }
}
