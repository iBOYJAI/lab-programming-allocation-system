<?php

/**
 * Submit Code - AJAX Handler
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

$question_id = intval($_POST['question_id'] ?? 0);
$allocation_id = intval($_POST['allocation_id'] ?? 0);
$code = $_POST['code'] ?? '';
$submission_key = $_POST['submission_key'] ?? '';
$is_auto = intval($_POST['is_auto'] ?? 0);

if (!$question_id || !$allocation_id || empty($code)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Check if already submitted
    $existing = dbQuery(
        "SELECT id FROM submissions 
                      WHERE allocation_id = ? AND question_id = ?",
        [$allocation_id, $question_id]
    )[0] ?? null;

    $is_rework = ($existing !== null);

    // Get session info to verify key if it's a rework
    $session_sql = "SELECT es.id as session_id, es.current_submission_key 
                    FROM exam_sessions es 
                    INNER JOIN student_allocations sa ON es.id = sa.session_id 
                    WHERE sa.id = ?";
    $session_info = dbQuery($session_sql, [$allocation_id])[0] ?? null;

    if (!$session_info) {
        echo json_encode(['success' => false, 'error' => 'Session not found']);
        exit;
    }

    // Verify submission key ONLY for reworks (skip if auto-submitting)
    if ($is_rework && !$is_auto) {
        if (empty($submission_key)) {
            echo json_encode(['success' => false, 'error' => 'Submission key required for rework']);
            exit;
        }
        if ($submission_key !== $session_info['current_submission_key']) {
            echo json_encode(['success' => false, 'error' => 'Invalid or expired submission key']);
            exit;
        }
    }

    if ($is_rework) {
        // Update existing submission
        $sql = "UPDATE submissions SET submitted_code = ?, submitted_at = NOW() WHERE id = ?";
        $executed = dbExecute($sql, [$code, $existing['id']]);
    } else {
        // First time submission
        $sql = "INSERT INTO submissions (allocation_id, question_id, submitted_code, submitted_at) VALUES (?, ?, ?, NOW())";
        $executed = dbExecute($sql, [$allocation_id, $question_id, $code]);
    }

    if ($executed) {
        // Update allocation status (always set to Submitted)
        if ($question_id == dbQuery("SELECT question_1_id FROM student_allocations WHERE id = ?", [$allocation_id])[0]['question_1_id']) {
            dbExecute("UPDATE student_allocations SET status_q1 = 'Submitted' WHERE id = ?", [$allocation_id]);
        } else {
            dbExecute("UPDATE student_allocations SET status_q2 = 'Submitted' WHERE id = ?", [$allocation_id]);
        }

        logActivity($_SESSION['user_id'], 'student', 'submit_code', ($is_rework ? "Updated" : "Submitted") . " code for question $question_id");

        echo json_encode(['success' => true, 'is_rework' => $is_rework]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
} catch (Exception $e) {
    error_log("Submit error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
