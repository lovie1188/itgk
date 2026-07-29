<?php
require_once '../config/auth.php';
require_once '../config/connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id = $_POST['id'] ?? null;

if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid learner ID']);
    exit;
}

try {
    // Build update query dynamically
    $fields = [];
    $values = [];
    $allowedFields = [
        'itgk_code',
        'learner_code',
        'learner_name',
        'father_name',
        'total_marks',
        'marks_obtained',
        'percentage',
        'result',
        'certificate_no',
        'course_name',
        'exam_name',
        'exam_date',
        'status',
        'remark',
        'receiving_date'
    ];

    foreach ($allowedFields as $field) {
        if (isset($_POST[$field])) {
            $fields[] = "$field = ?";
            $values[] = $_POST[$field];
        }
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }

    $values[] = $id; // Add ID for WHERE clause

    $sql = "UPDATE itgk_learner_result SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute($values);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Learner updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update learner']);
    }
} catch (Exception $e) {
    error_log("Error updating learner: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
