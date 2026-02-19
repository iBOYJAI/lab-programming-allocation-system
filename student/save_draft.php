<?php

/**
 * Save Code Draft - AJAX Handler
 */

header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/security.php';

startSecureSession();

if ($_SESSION['user_role'] !== 'student') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

$allocation_id = intval($_POST['allocation_id'] ?? 0);
$q1_code = $_POST['q1_code'] ?? null;
$q2_code = $_POST['q2_code'] ?? null;

if (!$allocation_id) {
    echo json_encode(['success' => false, 'error' => 'Missing allocation ID']);
    exit;
}

try {
    // Verify allocation belongs to student
    $student_id = $_SESSION['user_id'];
    $check = dbQuery("SELECT id, is_completed FROM student_allocations WHERE id = ? AND student_id = ?", [$allocation_id, $student_id]);

    if (empty($check)) {
        echo json_encode(['success' => false, 'error' => 'Allocation not found']);
        exit;
    }

    if ($check[0]['is_completed']) {
        echo json_encode(['success' => false, 'error' => 'Exam already completed']);
        exit;
    }

    // Update draft codes
    $update_parts = [];
    $params = [];

    if ($q1_code !== null) {
        $update_parts[] = "draft_code_q1 = ?";
        $params[] = $q1_code;
    }

    if ($q2_code !== null) {
        $update_parts[] = "draft_code_q2 = ?";
        $params[] = $q2_code;
    }

    if (empty($update_parts)) {
        echo json_encode(['success' => true, 'message' => 'Nothing to update']);
        exit;
    }

    $sql = "UPDATE student_allocations SET " . implode(", ", $update_parts) . " WHERE id = ?";
    $params[] = $allocation_id;

    if (dbExecute($sql, $params)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
