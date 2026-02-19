<?php

/**
 * Update Exam Progress Status
 * Marks student status as 'In Progress' for both questions upon entering the exam.
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$student_id = $_SESSION['user_id'];
$active_session = getActiveSessionForStudent($student_id);

if (!$active_session) {
    echo json_encode(['success' => false, 'error' => 'No active exam session found']);
    exit;
}

// Update both statuses to 'In Progress' and record System IP
$system_ip = $_SERVER['REMOTE_ADDR'];
$result = dbExecute("
    UPDATE student_allocations 
    SET status_q1 = IF(status_q1 = 'Not Started', 'In Progress', status_q1),
        status_q2 = IF(status_q2 = 'Not Started', 'In Progress', status_q2),
        system_ip = ?
    WHERE session_id = ? AND student_id = ?
", [$system_ip, $active_session['id'], $student_id]);

echo json_encode(['success' => true]);
