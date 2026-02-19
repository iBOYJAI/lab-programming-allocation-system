<?php

/**
 * AJAX Handler for fetching unread notification count
 */
require_once '../config/database.php';
require_once '../includes/security.php';

startSecureSession();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$admin_id = $_SESSION['user_id'];
$unread_stats = dbQuery("
    SELECT COUNT(*) as count FROM notifications 
    WHERE ((user_id = ? AND user_role = 'admin') OR (user_id IS NULL AND user_role = 'admin'))
    AND is_read = 0
", [$admin_id]);

echo json_encode(['count' => $unread_stats[0]['count'] ?? 0]);
