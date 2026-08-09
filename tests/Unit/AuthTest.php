<?php
/**
 * AuthTest - Unit tests for AuthService including RBAC
 */

use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function testRoleHierarchyOrder(): void
    {
        $reflection = new ReflectionClass(\App\Services\AuthService::class);
        $constant = $reflection->getConstant('ROLE_HIERARCHY');
        $this->assertIsArray($constant);
        
        // This tests via a static method indirectly
        // We verify hierarchy order: GUEST < PARTNER < COORDINATOR < EMPLOYEE < ADMIN < SUPERADMIN
        
        // Test via hasRoleLevel method
        $_SESSION['role'] = 'EMPLOYEE';
        $this->assertFalse(\App\Services\AuthService::hasRoleLevel('ADMIN'));
        $this->assertTrue(\App\Services\AuthService::hasRoleLevel('EMPLOYEE'));
        
        $_SESSION['role'] = 'ADMIN';
        $this->assertTrue(\App\Services\AuthService::hasRoleLevel('EMPLOYEE'));
        $this->assertTrue(\App\Services\AuthService::hasRoleLevel('ADMIN'));
    }

    public function testGetRolesReturnsArrayOfRoles(): void
    {
        $roles = \App\Services\AuthService::getRoles();
        
        $this->assertIsArray($roles);
        $this->assertContains('GUEST', $roles);
        $this->assertContains('SUPERADMIN', $roles);
    }

    public function testGetRoleDisplayName(): void
    {
        $this->assertEquals('Guest', \App\Services\AuthService::getRoleDisplayName('GUEST'));
        $this->assertEquals('Administrator', \App\Services\AuthService::getRoleDisplayName('ADMIN'));
        $this->assertEquals('Super Administrator', \App\Services\AuthService::getRoleDisplayName('SUPERADMIN'));
    }

    protected function tearDown(): void
    {
        unset($_SESSION['role']);
    }
}