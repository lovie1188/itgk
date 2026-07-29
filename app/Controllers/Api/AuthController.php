<?php

/**
 * API AuthController - Handles API authentication
 * 
 * @package App\Controllers\Api
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\AuthService;
use App\Helpers\Logger;

class AuthController extends BaseController
{
    /**
     * Handle user login
     * 
     * @return void
     */
    public function login(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->json(['success' => false, 'message' => 'Username and password required'], 400);
            return;
        }

        try {
            $user = AuthService::attempt($username, $password);
            
            // Generate JWT token
            $token = AuthService::generateJWT([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]);

            // Store token for logout if needed
            $_SESSION['api_token'] = $token;

            $this->json([
                'success' => true,
                'data' => [
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Logger::error('API login failed', ['error' => $e->getMessage()]);
            $this->json(['success' => false, 'message' => 'Invalid credentials'], 401);
        }
    }

    /**
     * Handle user logout
     * 
     * @return void
     */
    public function logout(): void
    {
        AuthService::logout();
        $this->json(['success' => true, 'message' => 'Logged out successfully']);
    }

    /**
     * Refresh JWT token
     * 
     * @return void
     */
    public function refresh(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $refreshToken = $input['refresh_token'] ?? '';

        if (!$refreshToken) {
            $this->json(['success' => false, 'message' => 'Refresh token required'], 400);
            return;
        }

        // In a full implementation, validate and issue new token
        $this->json(['success' => true, 'message' => 'Token refreshed']);
    }

    /**
     * SSO redirect
     * 
     * @return void
     */
    public function ssoRedirect(): void
    {
        // Redirect to web SSO
        $this->redirect('/auth/sso');
    }

    /**
     * SSO callback
     * 
     * @return void
     */
    public function callback(): void
    {
        // Handle callback via web
        $this->redirect('/auth/callback');
    }
}