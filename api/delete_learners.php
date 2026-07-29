<?php
require_once '../config/auth.php';
require_once '../config/connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = $_POST['action'] ?? '';
$ids = $_POST['ids'] ?? '';

if ($action !== 'delete' || empty($ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$idsArray = json_decode($ids, true);

if (!is_array($idsArray) || empty($idsArray)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid IDs format']);
    exit;
}

// Validate all IDs are numeric
foreach ($idsArray as $id) {
    if (!is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid ID format']);
        exit;
    }
}

try {
    // Create placeholders for the IN clause
    $placeholders = str_repeat('?,', count($idsArray) - 1) . '?';

    $sql = "DELETE FROM itgk_learner_result WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($idsArray);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Learners deleted successfully',
            'deleted_count' => $stmt->rowCount()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete learners']);
    }
} catch (Exception $e) {
    error_log("Error deleting learners: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
