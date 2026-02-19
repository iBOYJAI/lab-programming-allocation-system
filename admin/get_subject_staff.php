<?php

/**
 * AJAX - Get Staff IDs assigned to a subject
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

$subject_id = $_GET['subject_id'] ?? null;

if (!$subject_id) {
    die(json_encode(['success' => false, 'message' => 'Subject ID required']));
}

try {
    $staff = dbQuery("SELECT staff_id FROM staff_assignments WHERE subject_id = ?", [$subject_id]);
    $staff_ids = array_map('intval', array_column($staff, 'staff_id'));

    echo json_encode(['success' => true, 'staff_ids' => $staff_ids]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
