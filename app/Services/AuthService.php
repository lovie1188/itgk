<?php

/**
 * AuthService - Hybrid Authentication Service
 * 
 * Handles user authentication with both session-based (web) and JWT (API) support.
 * Provides unified interface for checking auth status, user info, and role validation.
 * 
 * @package App\Services
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AuthException;
use App\Helpers\Logger;

class AuthService
{
    /**
     * JWT secret key for signing tokens
     * @var string
     */
    private static string $jwtSecret = '';

    /**
     * JWT algorithm (HS256 for HMAC-SHA256)
     * @var string
     */
    private static string $jwtAlgo = 'HS256';

    /**
     * JWT token lifetime in seconds (default 24 hours)
     * @var int
     */
    private static int $jwtTTL = 86400;

    /**
     * Role hierarchy for authorization checks (lowest to highest privilege)
     * Matches database roles: GUEST < PARTNER < COORDINATOR < EMPLOYEE < ADMIN < SUPERADMIN
     */
    private const ROLE_HIERARCHY = [
        'GUEST' => 0,
        'PARTNER' => 10,
        'COORDINATOR' => 20,
        'EMPLOYEE' => 30,
        'ADMIN' => 40,
        'SUPERADMIN' => 100
    ];

    /**
     * Initialize JWT settings from environment
     * @return void
     */
    private static function initJWT(): void
    {
        if (empty(self::$jwtSecret)) {
            self::$jwtSecret = getenv('JWT_SECRET') ?: bin2hex(random_bytes(32));
            self::$jwtTTL = (int)(getenv('JWT_TTL') ?: 86400);
        }
    }

    /**
     * Session lifetime in seconds (default 2 hours)
     * @var int
     */
    private static int $sessionLifetime = 7200;

    /**
     * Check if user is authenticated (via session or JWT)
     * 
     * Checks both session-based auth (for web) and JWT (for API).
     * 
     * @return bool
     */
    public static function check(): bool
    {
        // Session-based auth check
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            return true;
        }

        // JWT-based auth check
        $token = self::getTokenFromRequest();
        if ($token && self::validateJWT($token)) {
            return true;
        }

        return false;
    }

    /**
     * Check if session has expired
     * 
     * @return bool
     */
    public static function isSessionExpired(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return true;
        }

        // Check if login time is set
        if (!isset($_SESSION['login_time'])) {
            return true;
        }

        // Check if session has exceeded lifetime
        $elapsed = time() - $_SESSION['login_time'];
        return $elapsed > self::$sessionLifetime;
    }

    /**
     * Update session activity timestamp
     * 
     * @return void
     */
    public static function updateActivity(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['last_activity'] = time();
        }
    }

    /**
     * Log in a user (session-based for web)
     * 
     * @param array $user User data
     * @param string $role User role
     * @return void
     */
    public static function login(array $user, string $role = 'EMPLOYEE'): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user['id'] ?? null;
        $_SESSION['username'] = $user['username'] ?? null;
        $_SESSION['email'] = $user['email'] ?? null;
        $_SESSION['name'] = $user['name'] ?? $user['first_name'] ?? $user['username'] ?? 'User';
        $_SESSION['role'] = $role;
        $_SESSION['user'] = $user;
        $_SESSION['login_time'] = time();

        Logger::info('User session authenticated', [
            'user_id' => $user['id'] ?? null,
            'username' => $user['username'] ?? null,
            'role' => $role
        ]);
    }

    /**
     * Log out user (clear session)
     * @return void
     */
    public static function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        $username = $_SESSION['username'] ?? null;

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        Logger::info('User logged out', [
            'user_id' => $userId,
            'username' => $username
        ]);
    }

    /**
     * Get current user from session or JWT
     * 
     * @return array|null User data or null if not authenticated
     */
    public static function user(): ?array
    {
        // Session-based user
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }

        // JWT-based user
        $token = self::getTokenFromRequest();
        if ($token) {
            $payload = self::validateJWT($token);
            if ($payload) {
                return $payload;
            }
        }

        return null;
    }

    /**
     * Get current user ID
     * 
     * @return int|null
     */
    public static function id(): ?int
    {
        // Session-based
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }

        // JWT-based
        $token = self::getTokenFromRequest();
        if ($token) {
            $payload = self::validateJWT($token);
            if ($payload && isset($payload['user_id'])) {
                return (int)$payload['user_id'];
            }
        }

        return null;
    }

    /**
     * Get current user role
     * 
     * @return string Role name or 'GUEST' if not authenticated
     */
    public static function role(): string
    {
        // Session-based
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['role'])) {
            return $_SESSION['role'];
        }

        // JWT-based
        $token = self::getTokenFromRequest();
        if ($token) {
            $payload = self::validateJWT($token);
            if ($payload && isset($payload['role'])) {
                return $payload['role'];
            }
        }

        return 'GUEST';
    }

    /**
     * Check if user has a specific role
     * 
     * @param string ...$roles Roles to check
     * @return bool
     */
    public static function hasRole(string ...$roles): bool
    {
        $userRole = self::role();
        return in_array($userRole, $roles, true);
    }

    /**
     * Require a specific role (throw exception if not met)
     * 
     * @param string ...$roles Required roles
     * @throws AuthException
     */
    public static function requireRole(string ...$roles): void
    {
        if (!self::hasRole(...$roles)) {
            throw new AuthException('Insufficient permissions', 403);
        }
    }

    /**
     * Check if user has at least the specified role level
     * 
     * @param string $role Minimum role level
     * @return bool
     */
    public static function hasRoleLevel(string $role): bool
    {
        $hierarchy = self::ROLE_HIERARCHY;
        $userLevel = $hierarchy[self::role()] ?? 0;
        $requiredLevel = $hierarchy[$role] ?? 0;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Require minimum role level (throw exception if not met)
     * 
     * @param string $role Minimum role level required
     * @throws AuthException
     */
    public static function requireRoleLevel(string $role): void
    {
        if (!self::hasRoleLevel($role)) {
            throw new AuthException('Insufficient role level', 403);
        }
    }

    /**
     * Check if user is SUPERADMIN
     * @return bool
     */
    public static function isSuperAdmin(): bool
    {
        return self::role() === 'SUPERADMIN';
    }

    /**
     * Check if user is ADMIN or higher
     * @return bool
     */
    public static function isAdmin(): bool
    {
        return self::hasRoleLevel('ADMIN');
    }

    /**
     * Check if user has permission
     * 
     * Checks if the permission is in the user's permission list from session.
     * 
     * @param string $permission Permission to check
     * @return bool
     */
    public static function can(string $permission): bool
    {
        // Super admin has all permissions
        if (self::isSuperAdmin()) {
            return true;
        }

        // Check if permission is in the user's permission list from session
        $permissions = $_SESSION['permissions'] ?? [];

        // Check for wildcard permission
        if (in_array('*', $permissions)) {
            return true;
        }

        return in_array($permission, $permissions);
    }

    /**
     * Require permission (throw exception if not met)
     * 
     * @param string $permission Required permission
     * @throws AuthException
     */
    public static function requirePermission(string $permission): void
    {
        if (!self::can($permission)) {
            throw new AuthException('Permission denied', 403);
        }
    }

    /**
     * Get all available roles
     * @return array
     */
    public static function getRoles(): array
    {
        return array_keys(self::ROLE_HIERARCHY);
    }

    /**
     * Get role display name
     * 
     * @param string $role Role name
     * @return string
     */
    public static function getRoleDisplayName(string $role): string
    {
        return match ($role) {
            'SUPERADMIN' => 'Super Administrator',
            'ADMIN' => 'Administrator',
            'EMPLOYEE' => 'Employee',
            'GUEST' => 'Guest',
            default => $role
        };
    }

    /**
     * Attempt user login with username and password
     * 
     * Queries the database for user authentication.
     * Supports login via username, email, or mobile.
     * 
     * @param string $username Username, email, or mobile
     * @param string $password Password
     * @return array User data on success
     * @throws AuthException On authentication failure
     */
    public static function attempt(string $username, string $password): array
    {
        // 1. Validate input
        if (empty($username) || empty($password)) {
            throw new AuthException('Username and password are required', 401);
        }

        // 2. Get database connection
        try {
            $db = \App\Core\Database::getInstance();
        } catch (\Exception $e) {
            Logger::error('Database connection failed during login', [
                'error' => $e->getMessage()
            ]);
            throw new AuthException('Service temporarily unavailable', 503);
        }

        // 3. Query user by username, email, or mobile (including designation & office mapping)
        $sql = "SELECT u.id, u.username, u.password, u.first_name, u.last_name, u.email, u.mobile, u.designation,
                       r.id as role_id, r.name as role_name,
                       o.id as office_id, o.name as office_name
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                LEFT JOIN employee_office_map eom ON u.id = eom.user_id
                LEFT JOIN offices o ON eom.office_id = o.id
                WHERE u.username = :input1 OR u.email = :input2 OR u.mobile = :input3 
                LIMIT 1";

        $user = $db->fetch($sql, [
            'input1' => $username,
            'input2' => $username,
            'input3' => $username
        ]);

        // 4. Check if user exists
        if (!$user) {
            Logger::warning('Login attempt with non-existent user', ['username' => $username]);
            throw new AuthException('Invalid username or password', 401);
        }

        // 5. Verify password
        if (!isset($user['password']) || !password_verify($password, $user['password'])) {
            Logger::warning('Invalid password attempt', ['user_id' => $user['id']]);
            throw new AuthException('Invalid username or password', 401);
        }

        // 6. Get user permissions
        $permissions = [];
        if ($user['role_id']) {
            $permSql = "SELECT p.name FROM role_permissions rp 
                        INNER JOIN permissions p ON rp.permission_id = p.id 
                        WHERE rp.role_id = :role_id";
            $permResult = $db->fetchAll($permSql, ['role_id' => $user['role_id']]);
            $permissions = array_column($permResult, 'name');
        }

        // 7. Prepare user data for session
        $userData = [
            'id' => $user['id'],
            'username' => $user['username'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'email' => $user['email'],
            'mobile' => $user['mobile'],
            'designation' => $user['designation'] ?? null,
            'office_id' => $user['office_id'] ?? null,
            'office_name' => $user['office_name'] ?? null,
            'role_id' => $user['role_id'],
            'role_name' => $user['role_name'],
            'permissions' => $permissions
        ];

        // 8. Log the successful login
        Logger::info('User login successful', [
            'user_id' => $user['id'],
            'username' => $username,
            'role' => $user['role_name']
        ]);

        // 9. Set up session
        self::login($userData, $user['role_name'] ?? 'EMPLOYEE');

        // 10. Store permissions in session
        $_SESSION['permissions'] = $permissions;

        // 11. Return user data (without sensitive info)
        return $userData;
    }

    /**
     * Generate JWT token for API authentication
     * 
     * @param array $data Token payload data
     * @param int|null $expiresIn Expiration time in seconds (null uses TTL)
     * @return string JWT token
     */
    public static function generateJWT(array $data, ?int $expiresIn = null): string
    {
        self::initJWT();
        $expiresIn ??= self::$jwtTTL;

        $header = [
            'alg' => self::$jwtAlgo,
            'typ' => 'JWT'
        ];

        $payload = array_merge($data, [
            'iat' => time(),
            'exp' => time() + $expiresIn,
            'nbf' => time()
        ]);

        $headerEncoded = self::base64URLEncode(json_encode($header));
        $payloadEncoded = self::base64URLEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            "{$headerEncoded}.{$payloadEncoded}",
            self::$jwtSecret,
            true
        );
        $signatureEncoded = self::base64URLEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * Validate and decode JWT token
     * 
     * Checks signature, expiration, and not-before time.
     * 
     * @param string $token JWT token to validate
     * @return array|false Payload array on success, false on failure
     */
    public static function validateJWT(string $token): array|false
    {
        self::initJWT();

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // Verify signature
        $signature = hash_hmac(
            'sha256',
            "{$headerEncoded}.{$payloadEncoded}",
            self::$jwtSecret,
            true
        );
        $expectedSignature = @base64_decode(strtr($signatureEncoded, '-_', '+/'));

        if (!$expectedSignature || !hash_equals($signature, $expectedSignature)) {
            return false;
        }

        // Decode payload
        $payload = @json_decode(base64_decode(strtr($payloadEncoded, '-_', '+/')), true);
        if (!$payload || !is_array($payload)) {
            return false;
        }

        // Validate expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        // Validate not-before
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            return false;
        }

        return $payload;
    }

    /**
     * Get JWT token from Authorization header
     * 
     * Looks for "Bearer <token>" format in Authorization header.
     * 
     * @return string|null Token or null if not found
     */
    private static function getTokenFromRequest(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        if (!$authHeader) {
            return null;
        }

        if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Base64 URL-safe encode
     * 
     * Uses URL-safe alphabet and removes padding.
     * 
     * @param string $data Data to encode
     * @return string Encoded data
     */
    private static function base64URLEncode(string $data): string
    {
        return strtr(rtrim(base64_encode($data), '='), '+/', '-_');
    }
}
