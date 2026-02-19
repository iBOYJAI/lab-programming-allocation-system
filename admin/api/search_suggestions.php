<?php

/**
 * Search Suggestions API
 * Returns JSON suggestions for the admin search bar
 */

require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/security.php';

// Ensure admin only
startSecureSession();
if (!isset($_GET['q']) || empty($_GET['q'])) {
    echo json_encode([]);
    exit;
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = $_GET['q'];
$search = "%$query%";
$suggestions = [];

// Students
$students = dbQuery("
    SELECT id, name, roll_number, 'student' as type 
    FROM students 
    WHERE name LIKE ? OR roll_number LIKE ? 
    LIMIT 3
", [$search, $search]);

foreach ($students as $s) {
    $suggestions[] = [
        'label' => $s['name'],
        'sub' => $s['roll_number'],
        'type' => 'student',
        'url' => 'manage_students.php?search=' . $s['roll_number'],
        'icon' => 'ni-user.svg'
    ];
}

// Staff
$staff = dbQuery("
    SELECT id, name, staff_id, 'staff' as type 
    FROM staff 
    WHERE name LIKE ? OR staff_id LIKE ? 
    LIMIT 3
", [$search, $search]);

foreach ($staff as $s) {
    $suggestions[] = [
        'label' => $s['name'],
        'sub' => $s['staff_id'],
        'type' => 'staff',
        'url' => 'manage_staff.php?search=' . $s['staff_id'],
        'icon' => 'ni-briefcase.svg' // Using fallback or specific icon
    ];
}

// Subjects
$subjects = dbQuery("
    SELECT id, subject_name, subject_code, 'subject' as type 
    FROM subjects 
    WHERE subject_name LIKE ? OR subject_code LIKE ? 
    LIMIT 3
", [$search, $search]);

foreach ($subjects as $s) {
    $suggestions[] = [
        'label' => $s['subject_name'],
        'sub' => $s['subject_code'],
        'type' => 'subject',
        'url' => 'manage_subjects.php?search=' . $s['subject_code'], // Assuming filter logic exists/will exist
        'icon' => 'ni-collection.svg'
    ];
}

// Labs
$labs = dbQuery("
    SELECT id, lab_name, location, 'lab' as type 
    FROM labs 
    WHERE lab_name LIKE ? OR location LIKE ? 
    LIMIT 3
", [$search, $search]);

foreach ($labs as $l) {
    $suggestions[] = [
        'label' => $l['lab_name'],
        'sub' => $l['location'],
        'type' => 'lab',
        'url' => 'manage_labs.php', // Generic link as labs manage page might not have deep link by ID yet
        'icon' => 'ni-building.svg' // Fallback
    ];
}

// Limit total
echo json_encode(array_slice($suggestions, 0, 8));
