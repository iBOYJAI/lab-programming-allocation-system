<?php
/**
 * One-time migration: Create departments table and add department_id columns
 * Run this once in browser: http://localhost/.../database/migrate_departments.php
 * Or from project root: php database/migrate_departments.php
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

$pdo = getDbConnection();
$dbname = DB_NAME;
$done = [];
$errors = [];

// 1. Create departments table
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS departments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            department_name VARCHAR(100) NOT NULL,
            department_code VARCHAR(20) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $done[] = "Created table: departments";
} catch (PDOException $e) {
    $errors[] = "departments table: " . $e->getMessage();
}

// Helper: add column and FK if column does not exist
$addDepartmentId = function ($table, $afterColumn) use ($pdo, $dbname, &$done, &$errors) {
    $stmt = $pdo->query("
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = " . $pdo->quote($dbname) . "
          AND TABLE_NAME = " . $pdo->quote($table) . "
          AND COLUMN_NAME = " . $pdo->quote('department_id') . "
        LIMIT 1
    ");
    if ($stmt->fetch()) {
        $done[] = "Column {$table}.department_id already exists";
        return;
    }
    try {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN department_id INT NULL AFTER `$afterColumn`");
        $pdo->exec("ALTER TABLE `$table` ADD FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL");
        $done[] = "Added department_id to table: $table";
    } catch (PDOException $e) {
        $errors[] = "$table: " . $e->getMessage();
    }
};

// 2. Add department_id to subjects (after lab_id)
$addDepartmentId('subjects', 'lab_id');

// 3. Add department_id to staff (after department)
$addDepartmentId('staff', 'department');

// 4. Add department_id to students (after department)
$addDepartmentId('students', 'department');

// Optional: seed default departments if table is empty
try {
    $count = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO departments (department_name, department_code, description) VALUES
            ('Computer Science', 'CS', 'Computer Science Department'),
            ('Information Technology', 'IT', 'Information Technology Department'),
            ('AI & DS', 'AIDS', 'Artificial Intelligence and Data Science Department')
        ");
        $done[] = "Inserted 3 default departments";
    }
} catch (PDOException $e) {
    $errors[] = "Seed departments: " . $e->getMessage();
}

// 5. Map existing staff/students/subjects to departments (so counts show correctly)
try {
    $pdo->exec("
        UPDATE staff s
        INNER JOIN departments d ON TRIM(s.department) = TRIM(d.department_name)
        SET s.department_id = d.id
        WHERE s.department IS NOT NULL AND TRIM(s.department) != ''
    ");
    $done[] = "Mapped existing staff to departments";
} catch (PDOException $e) {
    $errors[] = "Map staff: " . $e->getMessage();
}
try {
    $pdo->exec("
        UPDATE students s
        INNER JOIN departments d ON TRIM(s.department) = TRIM(d.department_name)
        SET s.department_id = d.id
        WHERE s.department IS NOT NULL AND TRIM(s.department) != ''
    ");
    $done[] = "Mapped existing students to departments";
} catch (PDOException $e) {
    $errors[] = "Map students: " . $e->getMessage();
}
try {
    $depts = $pdo->query("SELECT id, department_code FROM departments ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($depts as $d) {
        $like = ($d['department_code'] === 'AIDS') ? 'AI%' : $d['department_code'] . '%';
        $pdo->prepare("UPDATE subjects SET department_id = ? WHERE subject_code LIKE ? AND (department_id IS NULL OR department_id = 0)")->execute([$d['id'], $like]);
    }
    $done[] = "Mapped existing subjects to departments (by code prefix)";
} catch (PDOException $e) {
    $errors[] = "Map subjects: " . $e->getMessage();
}

echo "Migration finished.\n\n";
echo "Done:\n" . implode("\n", $done) . "\n\n";
if (!empty($errors)) {
    echo "Errors:\n" . implode("\n", $errors) . "\n";
} else {
    echo "No errors. Department Management will show correct Staff/Student/Subject counts.\n";
}
