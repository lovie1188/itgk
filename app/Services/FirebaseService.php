<?php

/**
 * FirebaseService - Standalone Firebase Authentication & User Sync Service
 * 
 * Provides native Firebase Auth integration (ID token verification, email/password,
 * Google Sign-In) and local session mapping without external SSO dependencies.
 * 
 * @package App\Services
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Logger;
use App\Exceptions\AuthException;
use Exception;

class FirebaseService
{
    /**
     * Firebase Web API Key
     * @var string
     */
    private string $apiKey;

    /**
     * Firebase Project ID
     * @var string
     */
    private string $projectId;

    /**
     * Firebase Identity Toolkit API URL
     * @var string
     */
    private string $authUrl = 'https://identitytoolkit.googleapis.com/v1';

    /**
     * Database instance
     * @var Database
     */
    private Database $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiKey = getenv('FIREBASE_API_KEY') ?: '';
        $this->projectId = getenv('FIREBASE_PROJECT_ID') ?: '';
        $this->db = Database::getInstance();
    }

    /**
     * Check if Firebase is enabled and configured
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        $enabled = filter_var(getenv('FIREBASE_ENABLED') ?: 'true', FILTER_VALIDATE_BOOLEAN);
        return $enabled && !empty($this->apiKey) && !empty($this->projectId);
    }

    /**
     * Get Firebase Config Array for Frontend JS SDK
     * 
     * @return array
     */
    public function getJsConfig(): array
    {
        return [
            'apiKey' => $this->apiKey,
            'authDomain' => getenv('FIREBASE_AUTH_DOMAIN') ?: "{$this->projectId}.firebaseapp.com",
            'projectId' => $this->projectId,
            'storageBucket' => getenv('FIREBASE_STORAGE_BUCKET') ?: "{$this->projectId}.firebasestorage.app",
            'messagingSenderId' => getenv('FIREBASE_MESSAGING_SENDER_ID') ?: '',
            'appId' => getenv('FIREBASE_APP_ID') ?: '',
            'measurementId' => getenv('FIREBASE_MEASUREMENT_ID') ?: ''
        ];
    }

    /**
     * Verify Firebase ID Token passed from frontend
     * 
     * @param string $idToken
     * @return array Firebase user profile data
     * @throws AuthException
     */
    public function verifyIdToken(string $idToken): array
    {
        if (empty($idToken)) {
            throw new AuthException('Missing Firebase ID token');
        }

        $url = "{$this->authUrl}/accounts:lookup?key={$this->apiKey}";

        try {
            $response = $this->makeRequest('POST', $url, [
                'idToken' => $idToken
            ]);

            if (empty($response['users'][0])) {
                throw new AuthException('Invalid or expired Firebase ID token');
            }

            $user = $response['users'][0];

            return [
                'firebase_uid' => $user['localId'] ?? '',
                'email' => $user['email'] ?? '',
                'email_verified' => !empty($user['emailVerified']),
                'display_name' => $user['displayName'] ?? '',
                'photo_url' => $user['photoUrl'] ?? ''
            ];
        } catch (Exception $e) {
            Logger::error('Firebase ID token verification failed', ['error' => $e->getMessage()]);
            throw new AuthException('Firebase authentication failed: ' . $e->getMessage());
        }
    }

    /**
     * Sign in user with Firebase Email and Password REST API
     * 
     * @param string $email
     * @param string $password
     * @return array Firebase user data + idToken
     * @throws AuthException
     */
    public function signInWithEmailPassword(string $email, string $password): array
    {
        if (!$this->isConfigured()) {
            throw new AuthException('Firebase Auth is not configured');
        }

        $url = "{$this->authUrl}/accounts:signInWithPassword?key={$this->apiKey}";

        try {
            $response = $this->makeRequest('POST', $url, [
                'email' => $email,
                'password' => $password,
                'returnSecureToken' => true
            ]);

            return [
                'firebase_uid' => $response['localId'] ?? '',
                'idToken' => $response['idToken'] ?? '',
                'refreshToken' => $response['refreshToken'] ?? '',
                'expiresIn' => $response['expiresIn'] ?? '3600',
                'email' => $response['email'] ?? $email,
                'display_name' => $response['displayName'] ?? ''
            ];
        } catch (Exception $e) {
            Logger::error('Firebase sign in failed', ['email' => $email, 'error' => $e->getMessage()]);
            throw new AuthException('Invalid email or password');
        }
    }

    /**
     * Map/Sync Firebase user into local MySQL users table and start session
     * 
     * @param array $firebaseUser Verified Firebase user data
     * @return array Local user data
     */
    public function syncAndLoginLocalUser(array $firebaseUser): array
    {
        $email = $firebaseUser['email'] ?? '';
        $uid = $firebaseUser['firebase_uid'] ?? '';
        $displayName = $firebaseUser['display_name'] ?? '';

        if (empty($email)) {
            throw new AuthException('Firebase user has no email address');
        }

        // Check if user exists in local database by email or firebase_uid
        $localUser = $this->db->fetch(
            "SELECT u.*, r.name as role_name, r.id as role_id,
                    o.id as office_id, o.name as office_name
             FROM users u
             LEFT JOIN user_roles ur ON u.id = ur.user_id
             LEFT JOIN roles r ON ur.role_id = r.id
             LEFT JOIN employee_office_map eom ON u.id = eom.user_id
             LEFT JOIN offices o ON eom.office_id = o.id
             WHERE u.email = ? OR u.firebase_uid = ? 
             ORDER BY eom.id DESC LIMIT 1",
            [$email, $uid]
        );

        if ($localUser) {
            // Update firebase_uid if missing
            if (empty($localUser['firebase_uid']) && !empty($uid)) {
                $this->db->update('users', ['firebase_uid' => $uid], 'id = ?', [$localUser['id']]);
                $localUser['firebase_uid'] = $uid;
            }
        } else {
            // Auto-provision local user account for authenticated Firebase user
            $parts = explode(' ', $displayName ?: $email);
            $firstName = $parts[0] ?? 'User';
            $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
            $username = strtolower(explode('@', $email)[0]);

            // Ensure unique username
            $existing = $this->db->fetchColumn("SELECT COUNT(*) FROM users WHERE username = ?", [$username]);
            if ($existing > 0) {
                $username .= '_' . rand(100, 999);
            }

            // Assign SUPERADMIN to specific superadmin emails (softtech.lovejeet@gmail.com / admin@admin.com)
            $defaultRole = in_array(strtolower($email), ['softtech.lovejeet@gmail.com', 'admin@admin.com']) ? 'SUPERADMIN' : 'PARTNER';

            $newUserId = (int)$this->db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'firebase_uid' => $uid,
                'is_active' => 1,
                'role' => $defaultRole,
                'active_role' => $defaultRole
            ]);

            // Get role ID
            $roleId = (int)$this->db->fetchColumn("SELECT id FROM roles WHERE name = ?", [$defaultRole]);
            if ($roleId) {
                $this->db->insert('user_roles', [
                    'user_id' => $newUserId,
                    'role_id' => $roleId
                ]);
            }

            $localUser = $this->db->fetch(
                "SELECT u.*, r.name as role_name, r.id as role_id,
                        o.id as office_id, o.name as office_name 
                 FROM users u
                 LEFT JOIN user_roles ur ON u.id = ur.user_id
                 LEFT JOIN roles r ON ur.role_id = r.id
                 LEFT JOIN employee_office_map eom ON u.id = eom.user_id
                 LEFT JOIN offices o ON eom.office_id = o.id
                 WHERE u.id = ? 
                 ORDER BY eom.id DESC LIMIT 1",
                [$newUserId]
            );
        }

        // Fetch user permissions
        $roleId = $localUser['role_id'] ?? null;
        $permissions = [];
        if ($roleId) {
            $permRows = $this->db->fetchAll(
                "SELECT p.name FROM role_permissions rp 
                 JOIN permissions p ON rp.permission_id = p.id 
                 WHERE rp.role_id = ?",
                [$roleId]
            );
            $permissions = array_column($permRows, 'name');
        }

        // Establish session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $roleName = strtoupper($localUser['role_name'] ?? $localUser['role'] ?? 'PARTNER');

        $_SESSION['user_id'] = $localUser['id'];
        $_SESSION['username'] = $localUser['username'];
        $_SESSION['email'] = $localUser['email'];
        $_SESSION['name'] = trim(($localUser['first_name'] ?? '') . ' ' . ($localUser['last_name'] ?? '')) ?: $localUser['username'];
        $_SESSION['role'] = $roleName;
        $_SESSION['user'] = [
            'id' => $localUser['id'],
            'username' => $localUser['username'],
            'first_name' => $localUser['first_name'],
            'last_name' => $localUser['last_name'],
            'name' => $_SESSION['name'],
            'email' => $localUser['email'],
            'mobile' => $localUser['mobile'] ?? '',
            'designation' => $localUser['designation'] ?? null,
            'office_id' => $localUser['office_id'] ?? null,
            'office_name' => $localUser['office_name'] ?? null,
            'role_name' => $roleName,
            'role_id' => $roleId,
            'permissions' => $permissions
        ];
        $_SESSION['login_time'] = time();

        Logger::info('User authenticated via Firebase', [
            'user_id' => $localUser['id'],
            'email' => $email,
            'role' => $roleName
        ]);

        return $_SESSION['user'];
    }

    /**
     * Make cURL HTTP request to Firebase Identity Toolkit REST API
     * 
     * @param string $method HTTP Method
     * @param string $url Endpoint URL
     * @param array $data JSON payload
     * @return array Response array
     * @throws Exception
     */
    private function makeRequest(string $method, string $url, array $data = []): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Firebase API cURL error: " . $error);
        }

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            $msg = $result['error']['message'] ?? 'Firebase API error (HTTP ' . $httpCode . ')';
            throw new Exception($msg);
        }

        return $result ?: [];
    }
}
