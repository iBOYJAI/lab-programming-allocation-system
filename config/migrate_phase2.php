<?php

/**
 * Database Migration - Phase 2
 * Linking students to multiple subjects and subjects to multiple staff.
 */
require_once __DIR__ . '/../config/database.php';

function runMigration()
{
    $pdo = getDbConnection();

    echo "Starting Migration Phase 2...\n";

    try {
        // 1. Create student_subjects table (Many-to-Many enrolment)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS student_subjects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                subject_id INT NOT NULL,
                enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('Active', 'Completed', 'Dropped') DEFAULT 'Active',
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
                UNIQUE KEY unique_enrolment (student_id, subject_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        echo "✓ Created table 'student_subjects'\n";

        // 2. Adjust staff_assignments (Ensure multi-staff is supported)
        // Table already has UNIQUE KEY unique_assignment (staff_id, lab_id, subject_id)
        // which allows multiple staff to be assigned to the same subject in the same lab.
        echo "✓ Table 'staff_assignments' already supports many-to-many relationship.\n";

        // 3. Migrate existing data if necessary
        // Currently, subjects are linked to lab_id. We might want to link students to subjects based on their department/semester.
        // For now, we will leave it to the Admin to manually enroll students in subjects or provide a bulk enroll tool.

        echo "Migration Phase 2 Completed Successfully!\n";
    } catch (Exception $e) {
        die("Migration Failed: " . $e->getMessage() . "\n");
    }
}

// Run if called directly
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    runMigration();
}
