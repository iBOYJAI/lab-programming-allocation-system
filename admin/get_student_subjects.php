<?php

/**
 * AJAX - Get Subjects with Enrollment Status for Student
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

$student_id = $_GET['student_id'] ?? null;

if (!$student_id) {
    die(json_encode(['success' => false, 'message' => 'Student ID required']));
}

try {
    // Get all subjects
    $subjects = dbQuery("SELECT id, subject_name, subject_code FROM subjects ORDER BY subject_name");

    // Get enrolled subject IDs
    $enrolled = dbQuery("SELECT subject_id FROM student_subjects WHERE student_id = ?", [$student_id]);
    $enrolled_ids = array_column($enrolled, 'subject_id');

    foreach ($subjects as &$sub) {
        $sub['enrolled'] = in_array($sub['id'], $enrolled_ids);
    }

    echo json_encode(['success' => true, 'subjects' => $subjects]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
