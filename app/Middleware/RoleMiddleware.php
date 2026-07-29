<?php

/**
 * RoleMiddleware - Role-Based Access Control Middleware
 * 
 * Verifies that the user has the required role(s) before allowing access.
 * Works in conjunction with AuthMiddleware.
 * 
 * @package App\Middleware
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Middleware\MiddlewareInterface;
use App\Services\AuthService;
use App\Exceptions\AuthException;
use App\Helpers\Logger;

class RoleMiddleware implements MiddlewareInterface
{
    /**
     * Required roles
     * @var array
     */
    private array $roles;

    /**
     * Whether to check minimum level instead of exact role
     * @var bool
     */
    private bool $checkLevel;

    /**
     * Constructor
     * 
     * @param string ...$roles Required role(s)
     */
    public function __construct(string ...$roles)
    {
        $this->roles = $roles;
        $this->checkLevel = false;
    }

    /**
     * Create middleware that checks minimum role level
     * 
     * @param string $minimumRole Minimum required role
     * @return self
     */
    public static function minimum(string $minimumRole): self
    {
        $middleware = new self($minimumRole);
        $middleware->checkLevel = true;
        return $middleware;
    }

    /**
     * Handle the middleware
     * 
     * Checks if user has required role(s).
     * Note: AuthMiddleware should run first to ensure user is authenticated.
     * 
     * @return void
     * @throws AuthException If user lacks required role
     */
    public function handle(): void
    {
        // Ensure user is authenticated (AuthMiddleware should run first)
        if (!AuthService::check()) {
            throw AuthException::notAuthenticated();
        }

        $userRole = AuthService::role();

        if ($this->checkLevel) {
            // Check if user has at least the minimum role level
            if (!AuthService::hasRoleLevel($this->roles[0])) {
                Logger::warning('RoleMiddleware: Access denied - insufficient role level', [
                    'user_id' => AuthService::id(),
                    'user_role' => $userRole,
                    'required_minimum' => $this->roles[0]
                ]);

                throw AuthException::accessDenied($this->roles[0]);
            }
        } else {
            // Check if user has one of the exact required roles
            if (!AuthService::hasRole(...$this->roles)) {
                Logger::warning('RoleMiddleware: Access denied - role mismatch', [
                    'user_id' => AuthService::id(),
                    'user_role' => $userRole,
                    'required_roles' => $this->roles
                ]);

                throw AuthException::accessDenied(implode('|', $this->roles));
            }
        }

        Logger::debug('RoleMiddleware: Access granted', [
            'user_id' => AuthService::id(),
            'user_role' => $userRole
        ]);
    }
}
