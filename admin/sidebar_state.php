<?php

/**
 * Sidebar State Handler - Session-based storage
 */
require_once '../config/database.php';
require_once '../includes/security.php';

startSecureSession();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $state = $_POST['state'] ?? 'expanded';
    $_SESSION['sidebar_state'] = $state;
    echo json_encode(['success' => true, 'state' => $state]);
} else {
    $state = $_SESSION['sidebar_state'] ?? 'expanded';
    echo json_encode(['state' => $state]);
}
