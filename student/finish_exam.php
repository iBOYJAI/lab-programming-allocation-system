<?php

/**
 * Finish Exam - Action Handler
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();

if ($_SESSION['user_role'] !== 'student' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('dashboard.php');
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid security token';
    redirect('exam.php');
}

$allocation_id = intval($_POST['allocation_id'] ?? 0);
$student_id = $_SESSION['user_id'];

if (!$allocation_id) {
    redirect('dashboard.php');
}

try {
    // Check if session exists and is not already completed
    $check = dbQuery("SELECT id FROM student_allocations WHERE id = ? AND student_id = ? AND is_completed = 0", [$allocation_id, $student_id]);

    if (!empty($check)) {
        // Mark as completed
        dbExecute("UPDATE student_allocations SET is_completed = 1, completed_at = NOW() WHERE id = ?", [$allocation_id]);

        logActivity($student_id, 'student', 'finish_exam', "Finished exam allocation ID: $allocation_id");
        $_SESSION['success'] = 'Exam completed successfully. Your sessions has ended.';
    }

    redirect('dashboard.php');
} catch (Exception $e) {
    $_SESSION['error'] = 'Error finishing exam: ' . $e->getMessage();
    redirect('exam.php');
}
