<?php
/**
 * One-time: Map existing staff, students, and subjects to departments
 * so Department Management shows correct counts.
 * Run in browser once: .../database/map_departments_data.php
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

$pdo = getDbConnection();
$done = [];
$errors = [];

// 1. Map staff: set department_id where department (VARCHAR) matches department_name
try {
    $stmt = $pdo->exec("
        UPDATE staff s
        INNER JOIN departments d ON TRIM(s.department) = TRIM(d.department_name)
        SET s.department_id = d.id
        WHERE s.department IS NOT NULL AND TRIM(s.department) != ''
    ");
    $staff_mapped = $pdo->query("SELECT COUNT(*) FROM staff WHERE department_id IS NOT NULL")->fetchColumn();
    $done[] = "Staff: mapped to departments (total with department_id: $staff_mapped)";
} catch (PDOException $e) {
    $errors[] = "Staff mapping: " . $e->getMessage();
}

// 2. Map students: set department_id where department (VARCHAR) matches department_name
try {
    $pdo->exec("
        UPDATE students s
        INNER JOIN departments d ON TRIM(s.department) = TRIM(d.department_name)
        SET s.department_id = d.id
        WHERE s.department IS NOT NULL AND TRIM(s.department) != ''
    ");
    $student_mapped = $pdo->query("SELECT COUNT(*) FROM students WHERE department_id IS NOT NULL")->fetchColumn();
    $done[] = "Students: mapped to departments (total with department_id: $student_mapped)";
} catch (PDOException $e) {
    $errors[] = "Students mapping: " . $e->getMessage();
}

// 3. Map subjects: set department_id by subject_code prefix (CS->1, IT->2, AI->3)
try {
    $depts = $pdo->query("SELECT id, department_code FROM departments ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($depts as $d) {
        $code = $d['department_code'];
        $id = (int) $d['id'];
        // e.g. CS -> subject_code LIKE 'CS%', IT -> 'IT%', AIDS -> 'AI%'
        $like = ($code === 'AIDS') ? 'AI%' : $code . '%';
        $pdo->prepare("UPDATE subjects SET department_id = ? WHERE subject_code LIKE ? AND (department_id IS NULL OR department_id = 0)")
            ->execute([$id, $like]);
    }
    $subject_mapped = $pdo->query("SELECT COUNT(*) FROM subjects WHERE department_id IS NOT NULL")->fetchColumn();
    $done[] = "Subjects: mapped by code prefix (total with department_id: $subject_mapped)";
} catch (PDOException $e) {
    $errors[] = "Subjects mapping: " . $e->getMessage();
}

echo "Department mapping finished.\n\n";
echo "Done:\n" . implode("\n", $done) . "\n\n";
if (!empty($errors)) {
    echo "Errors:\n" . implode("\n", $errors) . "\n";
} else {
    echo "Refresh Department Management to see updated counts.\n";
}
