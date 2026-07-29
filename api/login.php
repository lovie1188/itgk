<?php
// File : api/login.php
// API Login Endpoint - Returns JSON response for AJAX login requests

require_once __DIR__ . '/../config/connection.php';

// Check if this is an AJAX request or form submit
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Support both JSON and form data
$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    // Regular form submit
    $data = $_POST;
}
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

// Configure session cookie path consistently with main app
$sessionCookiePath = getenv('BASE_URL') ?: '/';
session_set_cookie_params([
    'lifetime' => (int)(getenv('SESSION_LIFETIME') ?: 7200),
    'path' => $sessionCookiePath,
    'httponly' => true,
    'samesite' => 'Lax'
]);

header('Content-Type: application/json');

try {
    // Query user by username, email, or mobile
    $sql = "SELECT u.id, u.username, u.password, u.first_name, u.last_name, u.email, u.mobile, r.id as role_id, r.name as role_name
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            WHERE u.username = :input1 OR u.email = :input2 OR u.mobile = :input3 LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute(['input1' => $username, 'input2' => $username, 'input3' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && isset($user['password']) && password_verify($password, $user['password'])) {
        $role_id = $user['role_id'];
        
        // Get user permissions
        $perm_sql = "SELECT p.name FROM role_permissions rp 
                     INNER JOIN permissions p ON rp.permission_id = p.id 
                     WHERE rp.role_id = :role_id";
        $perm_stmt = $conn->prepare($perm_sql);
        $perm_stmt->execute(['role_id' => $role_id]);
        $permissions = $perm_stmt->fetchAll(PDO::FETCH_COLUMN);

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Set session variables
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'email' => $user['email'],
            'mobile' => $user['mobile'],
            'role_name' => $user['role_name'],
            'role_id' => $user['role_id'],
            'permissions' => $permissions
        ];
        
        // Backward Compatibility / Flat Session Variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['role'] = $user['role_name'];
        $_SESSION['login_time'] = time();

        unset($user['password']);

        echo json_encode([
            'success' => true,
            'redirect' => BASE_URL . 'dashboard',
            'user' => $user,
            'role' => $user['role_name'],
            'permissions' => $permissions
        ]);
    } else {
        // Web form submits should redirect back with error
        if (!$isAjax) {
            $error = urlencode('Invalid username or password');
            header('Location: ' . BASE_URL . 'login?error=' . $error . '&username=' . urlencode($username));
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
} catch (Exception $e) {
    // Log error internally but don't expose details
    error_log('Login error: ' . $e->getMessage());
    
    // Web form submits should redirect back with generic error
    if (!$isAjax) {
        $error = urlencode('Login failed. Please try again.');
        header('Location: ' . BASE_URL . 'login?error=' . $error);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Server error']);
}