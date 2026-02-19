<?php

/**
 * AJAX Suggestion API for Global Search
 */
require_once '../config/database.php';
require_once '../includes/security.php';

startSecureSession();
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$query = $_GET['q'] ?? '';
$results = [];

if (strlen($query) >= 2) {
    $search = "%$query%";

    // Students logic
    $students = dbQuery("SELECT 'Student' as type, name as title, roll_number as subtitle FROM students WHERE name LIKE ? OR roll_number LIKE ? LIMIT 3", [$search, $search]);

    // Staff logic
    $staff = dbQuery("SELECT 'Staff' as type, name as title, staff_id as subtitle FROM staff WHERE name LIKE ? OR staff_id LIKE ? LIMIT 3", [$search, $search]);

    // Subjects logic
    $subjects = dbQuery("SELECT 'Subject' as type, subject_name as title, subject_code as subtitle FROM subjects WHERE subject_name LIKE ? OR subject_code LIKE ? LIMIT 3", [$search, $search]);

    $results = array_merge($students, $staff, $subjects);
}

header('Content-Type: application/json');
echo json_encode($results);
