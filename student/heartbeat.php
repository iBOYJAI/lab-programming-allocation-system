<?php

/**
 * Heartbeat script to check current exam session status
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'inactive', 'reason' => 'unauthenticated']);
    exit;
}

$student_id = $_SESSION['user_id'];
$active_session = getActiveSessionForStudent($student_id);

header('Content-Type: application/json');

if (!$active_session) {
    echo json_encode(['status' => 'inactive', 'reason' => 'no_session']);
} else {
    // Server-side check for time expiry
    $status = $active_session['status'];

    if ($status === 'Active' && $active_session['duration_minutes'] > 0) {
        $duration_seconds = $active_session['duration_minutes'] * 60;
        $startTime = strtotime($active_session['started_at']);
        $endTime = $startTime + $duration_seconds;

        if (time() >= $endTime) {
            // Force complete the session
            dbExecute("UPDATE exam_sessions SET status = 'Completed', ended_at = NOW() WHERE id = ?", [$active_session['id']]);
            $status = 'Completed';
        }
    }

    echo json_encode([
        'status' => $status,
        'session_id' => $active_session['id']
    ]);
}
