<?php

/**
 * Get Department Data API
 * Returns subjects, staff, and students for a specific department
 */

require_once '../../config/database.php';
require_once '../../includes/security.php';

startSecureSession();
requireRole(['admin'], '../../auth.php');

header('Content-Type: application/json');

$department_id = intval($_GET['department_id'] ?? 0);

if ($department_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid department ID']);
    exit;
}

try {
    $conn = getDbConnection();
    
    // Get subjects
    $subjects = dbQuery("SELECT id, subject_name, subject_code FROM subjects WHERE department_id = ? ORDER BY subject_name", [$department_id]);
    
    // Get staff
    $staff = dbQuery("SELECT id, name, staff_id, email FROM staff WHERE department_id = ? ORDER BY name", [$department_id]);
    
    // Get students
    $students = dbQuery("SELECT id, name, roll_number, email FROM students WHERE department_id = ? ORDER BY name", [$department_id]);
    
    // Get department info
    $dept = dbQuery("SELECT * FROM departments WHERE id = ?", [$department_id]);
    
    echo json_encode([
        'success' => true,
        'department' => $dept[0] ?? null,
        'subjects' => $subjects,
        'staff' => $staff,
        'students' => $students,
        'counts' => [
            'subjects' => count($subjects),
            'staff' => count($staff),
            'students' => count($students)
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

