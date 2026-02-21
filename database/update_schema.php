<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDbConnection();

    // Add columns to student_allocations if they don't exist
    $allocations_columns = [
        'can_resubmit_q1' => 'BOOLEAN DEFAULT 0',
        'can_resubmit_q2' => 'BOOLEAN DEFAULT 0',
        'is_completed' => 'BOOLEAN DEFAULT 0',
        'completed_at' => 'TIMESTAMP NULL',
        'draft_code_q1' => 'LONGTEXT NULL',
        'draft_code_q2' => 'LONGTEXT NULL'
    ];

    foreach ($allocations_columns as $col => $def) {
        // Check if column exists
        $stmt = $conn->query("SHOW COLUMNS FROM student_allocations LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $sql = "ALTER TABLE student_allocations ADD COLUMN $col $def";
            if ($conn->exec($sql) !== false) {
                echo "Added column $col to student_allocations successfully.\n";
            } else {
                echo "Error adding column $col to student_allocations.\n";
            }
        } else {
            echo "Column $col already exists in student_allocations.\n";
        }
    }

    // Add columns to exam_sessions if they don't exist
    $sessions_columns = [
        'current_submission_key' => "VARCHAR(6) DEFAULT '000000'",
        'key_updated_at' => 'TIMESTAMP NULL'
    ];

    foreach ($sessions_columns as $col => $def) {
        // Check if column exists
        $stmt = $conn->query("SHOW COLUMNS FROM exam_sessions LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $sql = "ALTER TABLE exam_sessions ADD COLUMN $col $def";
            if ($conn->exec($sql) !== false) {
                echo "Added column $col to exam_sessions successfully.\n";
            } else {
                echo "Error adding column $col to exam_sessions.\n";
            }
        } else {
            echo "Column $col already exists in exam_sessions.\n";
        }
    }

    echo "Schema update completed.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
