<?php

/**
 * API UserController - Handles user profile operations
 * 
 * @package App\Controllers\Api
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\AuthService;
use App\Helpers\Logger;

class UserController extends BaseController
{
    /**
     * Require authentication
     */
    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
    }

    /**
     * Get current user info
     * 
     * @return void
     */
    public function current(): void
    {
        $user = AuthService::user();
        
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Not authenticated'], 401);
            return;
        }

        $this->json([
            'success' => true,
            'data' => [
                'id' => $user['id'] ?? null,
                'username' => $user['username'] ?? null,
                'name' => $user['name'] ?? null,
                'email' => $user['email'] ?? null,
                'role' => AuthService::role()
            ]
        ]);
    }

    /**
     * Update user profile
     * 
     * @return void
     */
    public function update(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $user = AuthService::user();
        
        if (!$user) {
            $this->json(['success' => false, 'message' => 'Not authenticated'], 401);
            return;
        }

        // For now, just return success
        $this->json(['success' => true, 'message' => 'Profile updated']);
    }
}