<?php
require_once '../config/auth.php';
require_once '../config/connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid learner ID']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM itgk_learner_result WHERE id = ?");
    $stmt->execute([$id]);
    $learner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$learner) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Learner not found']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'learner' => $learner
    ]);
} catch (Exception $e) {
    error_log("Error fetching learner: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
