<?php

/**
 * GET/Rotate Submission Key API
 */
require_once '../../config/database.php';
require_once '../../includes/security.php';
require_once '../../includes/functions.php';

startSecureSession();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$session_id = $_GET['session_id'] ?? null;

if (!$session_id) {
    echo json_encode(['success' => false, 'error' => 'Session ID required']);
    exit;
}

$key = rotateSessionKey($session_id);

echo json_encode([
    'success' => true,
    'key' => $key,
    'next_rotation' => 60 - (time() % 60) // Rough sync to minute
]);
