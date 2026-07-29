<?php

/**
 * AuthController - Authentication Controller
 * 
 * Handles user authentication, login, logout,
 * and SSO integration.
 * 
 * @package App\Controllers
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\SSOService;
use App\Helpers\Logger;

/**
 * AuthController Class
 * 
 * Controller for authentication operations.
 */
class AuthController extends BaseController
{
    /**
     * Display login page
     * 
     * @return void
     */
    public function login(): void
    {
        // If already logged in, redirect to dashboard
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
            return;
        }

        // Check feature flags
        $ssoEnabled = getenv('SSO_ENABLED') === 'true';
        $firebaseService = new \App\Services\FirebaseService();

        $data = [
            'title' => 'Login | SoftSam Portal',
            'ssoEnabled' => $ssoEnabled,
            'ssoUrl' => $ssoEnabled ? $this->getSsoLoginUrl() : null,
            'firebaseEnabled' => $firebaseService->isConfigured(),
            'firebaseConfig' => $firebaseService->getJsConfig()
        ];

        $this->view('pages/auth/login', $data, false);
    }

    /**
     * AJAX/POST: Verify Firebase ID Token and authenticate local session
     * 
     * @return void
     */
    public function firebaseVerify(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $idToken = $input['id_token'] ?? $input['idToken'] ?? '';

            if (empty($idToken)) {
                $this->json(['success' => false, 'message' => 'Firebase ID token is required'], 400);
                return;
            }

            $firebaseService = new \App\Services\FirebaseService();
            $fbUser = $firebaseService->verifyIdToken($idToken);
            $localUser = $firebaseService->syncAndLoginLocalUser($fbUser);

            $this->json([
                'success' => true,
                'message' => 'Authenticated via Firebase',
                'redirect' => BASE_URL . 'dashboard',
                'user' => $localUser
            ]);
        } catch (\Exception $e) {
            Logger::error('Firebase verify failed', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }

    /**
     * Handle user logout
     * 
     * @return void
     */
    public function logout(): void
    {
        // Log logout action
        if ($this->isAuthenticated()) {
            Logger::info('User logged out', [
                'user_id' => $_SESSION['user_id'] ?? null,
                'username' => $_SESSION['username'] ?? null
            ]);
        }

        // Unset all session variables
        $_SESSION = [];

        // Destroy the session cookie
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

        // Destroy the session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        // Redirect to login page
        $this->redirect('/login');
    }

    /**
     * Redirect to SSO provider
     * 
     * @return void
     */
    public function ssoRedirect(): void
    {
        // Check if SSO is enabled
        if (getenv('SSO_ENABLED') !== 'true') {
            $this->redirect('/login');
            return;
        }

        try {
            $ssoService = new SSOService();
            $authUrl = $ssoService->getAuthorizationUrl();

            if (!is_string($authUrl) || $authUrl === '') {
                Logger::error('SSO redirect failed - invalid authorization URL', ['url' => $authUrl]);
                $this->redirect('/login?error=sso_failed');
                return;
            }

            $this->redirect($authUrl);
        } catch (\Exception $e) {
            Logger::error('SSO redirect failed', ['error' => $e->getMessage()]);
            $this->redirect('/login?error=sso_failed');
        }
    }

    /**
     * Handle SSO callback
     * 
     * @return void
     */
    public function callback(): void
    {
        // Check if SSO is enabled
        if (getenv('SSO_ENABLED') !== 'true') {
            $this->redirect('/login');
            return;
        }

        $code = $_GET['code'] ?? null;
        $error = $_GET['error'] ?? null;

        if ($error) {
            Logger::error('SSO callback error', ['error' => $error]);
            $this->redirect('/login?error=' . urlencode($error));
            return;
        }

        if (!$code) {
            $this->redirect('/login?error=no_code');
            return;
        }

        try {
            $ssoService = new SSOService();
            $userData = $ssoService->handleCallback($code);

            // Sync user from SSO to local database
            $localUser = $this->syncUserFromSSO($userData);

            // Create session with local user data
            AuthService::login($localUser, $localUser['role'] ?? 'EMPLOYEE');

            // Mark session as SSO-based for "Back to SSO" feature
            $_SESSION['sso_login'] = true;

            Logger::info('SSO login successful', [
                'user_id' => $localUser['id'] ?? null,
                'username' => $localUser['username'] ?? null,
                'role' => $localUser['role'] ?? 'EMPLOYEE'
            ]);

            $this->redirect('/dashboard');
        } catch (\Exception $e) {
            Logger::error('SSO callback failed', ['error' => $e->getMessage()]);
            $this->redirect('/login?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Sync user from SSO to local database
     * 
     * Creates or updates local user record based on SSO data.
     * Handles users that may already exist in both databases.
     * - If user exists locally (matched by email/username), updates their info
     * - Preserves existing password if user was created locally
     * - Updates role assignment from SSO
     * 
     * @param array $ssoUser User data from SSO
     * @return array Local user data
     */
    private function syncUserFromSSO(array $ssoUser): array
    {
        $db = \App\Core\Database::getInstance();

        $ssoUserId = $ssoUser['user_id'] ?? $ssoUser['id'] ?? null;
        $username = $ssoUser['username'] ?? $ssoUser['email'] ?? '';
        $email = $ssoUser['email'] ?? '';
        $name = $ssoUser['name'] ?? '';
        $ssoRole = $ssoUser['role'] ?? 'EMPLOYEE';

        // Parse name into first/last
        $nameParts = explode(' ', $name, 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        // Check if user exists by email or username (handles users in both databases)
        $existingUser = $db->fetch(
            "SELECT u.*, r.name as current_role 
            FROM users u 
            LEFT JOIN user_roles ur ON u.id = ur.user_id 
            LEFT JOIN roles r ON ur.role_id = r.id 
            WHERE u.email = ? OR u.username = ?",
            [$email, $username]
        );

        if ($existingUser) {
            // User exists locally - update their info but preserve password
            $db->query(
                "UPDATE users SET 
                    email = ?, 
                    first_name = ?, 
                    last_name = ?,
                    updated_at = NOW()
                WHERE id = ?",
                [$email, $firstName, $lastName, $existingUser['id']]
            );

            $userId = $existingUser['id'];

            // Update role from SSO if different
            if (!empty($ssoRole) && $existingUser['current_role'] !== $ssoRole) {
                // Get the role ID for the SSO role
                $roleResult = $db->fetch(
                    "SELECT id FROM roles WHERE name = ?",
                    [$ssoRole]
                );

                if ($roleResult) {
                    // Check if user_roles entry exists
                    $existingRole = $db->fetch(
                        "SELECT * FROM user_roles WHERE user_id = ?",
                        [$userId]
                    );

                    if ($existingRole) {
                        // Update existing role
                        $db->query(
                            "UPDATE user_roles SET role_id = ? WHERE user_id = ?",
                            [$roleResult['id'], $userId]
                        );
                    } else {
                        // Insert new role
                        $db->query(
                            "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)",
                            [$userId, $roleResult['id']]
                        );
                    }
                }
            }

            Logger::info('SSO user synced to existing local account', [
                'user_id' => $userId,
                'username' => $username,
                'sso_role' => $ssoRole
            ]);
        } else {
            // Create new user from SSO data (no password - SSO auth only)
            $db->query(
                "INSERT INTO users (username, email, first_name, last_name, password, created_at, updated_at)
                VALUES (?, ?, ?, ?, '', NOW(), NOW())",
                [$username, $email, $firstName, $lastName]
            );

            $userId = $db->lastInsertId();

            // Assign role from SSO
            $roleResult = $db->fetch(
                "SELECT id FROM roles WHERE name = ?",
                [$ssoRole]
            );

            if ($roleResult) {
                $db->query(
                    "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)",
                    [$userId, $roleResult['id']]
                );
            }

            Logger::info('New user created from SSO', [
                'user_id' => $userId,
                'username' => $username,
                'sso_role' => $ssoRole
            ]);
        }

        // Get user with role
        $user = $db->fetch(
            "SELECT u.*, r.name as role_name 
            FROM users u 
            LEFT JOIN user_roles ur ON u.id = ur.user_id 
            LEFT JOIN roles r ON ur.role_id = r.id 
            WHERE u.id = ?",
            [$userId]
        );

        // Get permissions
        $permissions = [];
        if (!empty($user['role_name'])) {
            $permResult = $db->fetchAll(
                "SELECT p.name FROM role_permissions rp 
                INNER JOIN permissions p ON rp.permission_id = p.id 
                WHERE rp.role_id = (SELECT id FROM roles WHERE name = ?)",
                [$user['role_name']]
            );
            $permissions = array_column($permResult, 'name');
        }

        // Store permissions in session
        $_SESSION['permissions'] = $permissions;

        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'name' => trim($user['first_name'] . ' ' . $user['last_name']),
            'role' => $user['role_name'] ?? $ssoRole,
            'permissions' => $permissions
        ];
    }

    /**
     * Get SSO login URL
     * 
     * @return string|null
     */
    private function getSsoLoginUrl(): ?string
    {
        try {
            $ssoService = new SSOService();
            return $ssoService->getAuthorizationUrl();
        } catch (\Exception $e) {
            Logger::error('Failed to get SSO URL', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
