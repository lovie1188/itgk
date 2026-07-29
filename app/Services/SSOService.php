<?php

/**
 * SSOService - Single Sign-On Integration Service
 * 
 * Handles OAuth 2.0 authentication with SOFTTECH SSO server.
 * Provides standalone mode fallback when SSO is unavailable.
 * 
 * @package App\Services
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use App\Exceptions\AuthException;

class SSOService
{
    /**
     * SSO server URL
     * @var string
     */
    private string $ssoUrl;

    /**
     * Client ID
     * @var string
     */
    private string $clientId;

    /**
     * Client secret
     * @var string
     */
    private string $clientSecret;

    /**
     * Redirect URI
     * @var string
     */
    private string $redirectUri;

    /**
     * SSO asset URL
     * @var string
     */
    private string $assetUrl;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ssoUrl = getenv('SSO_URL') ?: 'http://localhost/softtechsso';
        $this->clientId = getenv('SSO_CLIENT_ID') ?: '';
        $this->clientSecret = getenv('SSO_CLIENT_SECRET') ?: '';
        $this->assetUrl = getenv('SSO_ASSET_URL') ?: '';
        // Must match exactly: the redirect_uri registered in oauth_clients table on SSO server
        // Registered as: http://localhost/certificate/auth/callback
        $this->redirectUri = rtrim(getenv('APP_URL') ?: 'http://localhost/certificate', '/') . '/auth/callback';
    }

    /**
     * Check if SSO is configured
     * 
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Check if SSO server is available
     * 
     * @return bool
     */
    public function isAvailable(): bool
    {
        static $available = null;

        if ($available === null) {
            $ch = curl_init($this->ssoUrl . '/health');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 5
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $available = $httpCode === 200;
        }

        return $available;
    }

    /**
     * Get SSO login redirect URL
     * 
     * @param string|null $state Optional state parameter
     * @return string
     */
    public function getLoginUrl(?string $state = null): string
    {
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
            'scope' => 'profile email roles'
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return $this->ssoUrl . '/oauth/authorize?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for access token
     * 
     * @param string $code Authorization code
     * @return array|null Token data or null on failure
     */
    public function getAccessToken(string $code): ?array
    {
        $ch = curl_init($this->ssoUrl . '/oauth/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'authorization_code',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'code' => $code
            ]),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            Logger::error('SSO token exchange failed', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Get user info from SSO server
     * 
     * @param string $accessToken Access token
     * @return array|null User data or null on failure
     */
    public function getUserInfo(string $accessToken): ?array
    {
        $ch = curl_init($this->ssoUrl . '/api/user');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            Logger::error('SSO user info fetch failed', [
                'http_code' => $httpCode,
                'response' => $response
            ]);
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Validate token with SSO server
     * 
     * @param string $token Token to validate
     * @return bool
     */
    public function validateToken(string $token): bool
    {
        $ch = curl_init($this->ssoUrl . '/api/token/validate');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Refresh access token
     * 
     * @param string $refreshToken Refresh token
     * @return array|null New token data or null on failure
     */
    public function refreshToken(string $refreshToken): ?array
    {
        $ch = curl_init($this->ssoUrl . '/oauth/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'refresh_token',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $refreshToken
            ]),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Logout from SSO server
     * 
     * @param string $token Access token
     * @return bool
     */
    public function logout(string $token): bool
    {
        $ch = curl_init($this->ssoUrl . '/api/logout');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * Get asset URL with fallback support
     * 
     * @param string $path Asset path (e.g., 'css/bootstrap.min.css')
     * @param bool $localFallback Whether to fallback to local assets
     * @return string
     */
    public static function asset(string $path, bool $localFallback = true): string
    {
        $ssoAssets = getenv('SSO_ASSET_URL');

        // Check if SSO assets are available
        if ($ssoAssets && self::isAssetsAvailable($ssoAssets)) {
            return rtrim($ssoAssets, '/') . '/' . ltrim($path, '/');
        }

        // Fallback to local assets
        if ($localFallback) {
            return BASE_URL . 'assets/vendor/' . ltrim($path, '/');
        }

        throw new \RuntimeException("SSO assets unavailable and no fallback configured");
    }

    /**
     * Check if SSO assets are available
     * 
     * @param string $ssoAssets SSO asset URL
     * @return bool
     */
    private static function isAssetsAvailable(string $ssoAssets): bool
    {
        static $available = [];

        $key = md5($ssoAssets);

        if (!isset($available[$key])) {
            $ch = curl_init(rtrim($ssoAssets, '/') . '/css/bootstrap.min.css');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_NOBODY => true,
                CURLOPT_TIMEOUT => 5
            ]);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $available[$key] = $httpCode === 200;
        }

        return $available[$key];
    }

    /**
     * Get SSO URL
     * 
     * @return string
     */
    public function getSsoUrl(): string
    {
        return $this->ssoUrl;
    }

    /**
     * Get asset URL
     * 
     * @return string
     */
    public function getAssetUrl(): string
    {
        return $this->assetUrl;
    }

    /**
     * Get authorization URL (alias for getLoginUrl)
     * 
     * @param string|null $state Optional state parameter
     * @return string
     */
    public function getAuthorizationUrl(?string $state = null): string
    {
        return $this->getLoginUrl($state);
    }

    /**
     * Handle OAuth callback and return user data
     * 
     * @param string $code Authorization code
     * @return array User data
     * @throws AuthException If callback handling fails
     */
    public function handleCallback(string $code): array
    {
        // Exchange code for access token
        $tokenData = $this->getAccessToken($code);

        if (!$tokenData || !isset($tokenData['access_token'])) {
            throw new AuthException('Failed to obtain access token from SSO server');
        }

        // Get user info
        $userInfo = $this->getUserInfo($tokenData['access_token']);

        if (!$userInfo) {
            throw new AuthException('Failed to get user info from SSO server');
        }

        // Store tokens in session for later use
        $_SESSION['sso_access_token'] = $tokenData['access_token'];
        $_SESSION['sso_refresh_token'] = $tokenData['refresh_token'] ?? null;

        return [
            'id' => $userInfo['id'] ?? null,
            'user_id' => $userInfo['id'] ?? null,
            'username' => $userInfo['username'] ?? $userInfo['email'] ?? '',
            'email' => $userInfo['email'] ?? '',
            'name' => $userInfo['name'] ?? $userInfo['username'] ?? '',
            'role' => $userInfo['role'] ?? 'EMPLOYEE',
            'roles' => $userInfo['roles'] ?? [],
            'permissions' => $userInfo['permissions'] ?? []
        ];
    }
}
