<?php

/**
 * Stop Exam
 */

require_once '../config/database.php';
require_once '../includes/security.php';

startSecureSession();
requireRole(['staff'], 'login.php');

$session_id = intval($_GET['session_id'] ?? 0);

if ($session_id) {
    dbExecute(
        "UPDATE exam_sessions SET status = 'Completed', ended_at = NOW() WHERE id = ? AND staff_id = ?",
        [$session_id, $_SESSION['user_id']]
    );

    logActivity($_SESSION['user_id'], 'staff', 'stop_exam', "Stopped exam session ID: $session_id");
}

redirect('dashboard.php');
