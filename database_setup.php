<?php
// Database Setup Script for Authentication System
require_once 'config/connection.php';

echo "<h1>Database Setup for Authentication System</h1>";

try {
    // Create roles table
    $sql = "CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "<p>✅ Roles table created successfully</p>";

    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        mobile VARCHAR(15) UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        is_active BOOLEAN DEFAULT TRUE,
        email_verified BOOLEAN DEFAULT FALSE,
        failed_login_attempts INT DEFAULT 0,
        last_failed_login TIMESTAMP NULL,
        locked_until TIMESTAMP NULL,
        password_reset_token VARCHAR(255) NULL,
        password_reset_expires TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "<p>✅ Users table created successfully</p>";

    // Create user_roles table
    $sql = "CREATE TABLE IF NOT EXISTS user_roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        role_id INT NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_role (user_id, role_id)
    )";
    $conn->exec($sql);
    echo "<p>✅ User roles table created successfully</p>";

    // Create permissions table (must exist before role_permissions reference)
    $sql = "CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "<p>✅ Permissions table created successfully</p>";

    // Create role_permissions table
    $sql = "CREATE TABLE IF NOT EXISTS role_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_id INT NOT NULL,
        permission_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
        UNIQUE KEY unique_role_permission (role_id, permission_id)
    )";
    $conn->exec($sql);
    echo "<p>✅ Role permissions table created successfully</p>";

    // Create login_attempts table for rate limiting
    $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        username VARCHAR(50),
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip_attempts (ip_address, attempted_at),
        INDEX idx_username_attempts (username, attempted_at)
    )";
    $conn->exec($sql);
    echo "<p>✅ Login attempts table created successfully</p>";

    // Insert default roles matching existing database structure
    // Existing: PARTNER(1), EMPLOYEE(2), ADMIN(3), SUPERADMIN(4)
    // Adding: COORDINATOR, GUEST
    $existingRoles = [
        'PARTNER', 'EMPLOYEE', 'ADMIN', 'SUPERADMIN'
    ];
    
    // Only insert roles that don't exist yet
    $checkRole = $conn->prepare("SELECT COUNT(*) FROM roles WHERE name = ?");
    $insertRole = $conn->prepare("INSERT INTO roles (name) VALUES (?)");
    
    foreach ($existingRoles as $role) {
        $checkRole->execute([$role]);
        if ($checkRole->fetchColumn() == 0) {
            $insertRole->execute([$role]);
        }
    }
    
    // Add COORDINATOR and GUEST if not present
    $checkRole->execute(['COORDINATOR']);
    if ($checkRole->fetchColumn() == 0) $insertRole->execute(['COORDINATOR']);
    
    $checkRole->execute(['GUEST']);
    if ($checkRole->fetchColumn() == 0) $insertRole->execute(['GUEST']);
    
    $checkRole->execute(['GUEST']);
    if ($checkRole->fetchColumn() == 0) $insertRole->execute(['GUEST']);
    
    echo "<p>✅ Roles verified/inserted successfully</p>";

    // Seed permissions table with defaults if empty
    $allPermissions = [
        'manage_users',
        'manage_roles',
        'manage_certificates',
        'manage_learners',
        'view_reports',
        'system_settings',
        'delete_records',
        'upload_data'
    ];
    $permInsert = $conn->prepare("INSERT IGNORE INTO permissions (name) VALUES (?)");
    foreach ($allPermissions as $permName) {
        $permInsert->execute([$permName]);
    }
    echo "<p>✅ Default permissions seeded successfully</p>";

    // Assign SUPERADMIN permissions via role_permissions (reference permission IDs)
    $roleId = $conn->query("SELECT id FROM roles WHERE name = 'SUPERADMIN'")->fetchColumn();
    if ($roleId) {
        // Collect permission IDs
        $permIds = $conn->query("SELECT id, name FROM permissions")->fetchAll(PDO::FETCH_KEY_PAIR);
        $rpInsert = $conn->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($allPermissions as $permName) {
            if (isset($permIds[$permName])) {
                $rpInsert->execute([$roleId, $permIds[$permName]]);
            }
        }
    }
    echo "<p>✅ SUPERADMIN role-permission mappings created successfully</p>";

    // Create a default SUPERADMIN user (you should change this password!)
    $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT IGNORE INTO users (username, email, password, first_name, last_name, is_active, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@softtechseva.com', $defaultPassword, 'Super', 'Admin', true, true]);

    // Assign SUPERADMIN role to default user
    $userId = $conn->lastInsertId();
    if ($userId) {
        $stmt = $conn->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)");
        $stmt->execute([$userId, $roleId]);
    }
    echo "<p>✅ Default SUPERADMIN user created successfully</p>";
    echo "<p><strong>Default login:</strong> admin / admin123</p>";
    echo "<p><strong style='color: red;'>⚠️ Please change the default password immediately!</strong></p>";

    echo "<h2>Setup Complete!</h2>";
    echo "<p>All authentication tables have been created and populated with default data.</p>";
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p style='color: red;'>Setup failed: " . $e->getMessage() . "</p>";
}
