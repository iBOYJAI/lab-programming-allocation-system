<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDbConnection();

    // Add columns to student_allocations if they don't exist
    $columns = [
        'can_resubmit_q1' => 'BOOLEAN DEFAULT 0',
        'can_resubmit_q2' => 'BOOLEAN DEFAULT 0',
        'is_completed' => 'BOOLEAN DEFAULT 0',
        'completed_at' => 'TIMESTAMP NULL',
        'draft_code_q1' => 'LONGTEXT NULL',
        'draft_code_q2' => 'LONGTEXT NULL'
    ];

    foreach ($columns as $col => $def) {
        // Check if column exists
        $stmt = $conn->query("SHOW COLUMNS FROM student_allocations LIKE '$col'");
        if ($stmt->rowCount() == 0) {
            $sql = "ALTER TABLE student_allocations ADD COLUMN $col $def";
            if ($conn->exec($sql) !== false) {
                echo "Added column $col successfully.\n";
            } else {
                echo "Error adding column $col.\n";
            }
        } else {
            echo "Column $col already exists.\n";
        }
    }

    echo "Schema update completed.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
