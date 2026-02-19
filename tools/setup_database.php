<?php

/**
 * Database Setup Script
 * Imports schema and seed data from database/lab_allocation_system.sql
 */

require_once __DIR__ . '/../config/database.php';

$sqlFile = __DIR__ . '/../database/lab_allocation_system.sql';

if (!file_exists($sqlFile)) {
    die("❌ Error: SQL file not found at $sqlFile");
}

echo "<h2>Database Setup & Seeding</h2>";
echo "<p>Reading SQL file...</p>";

$sqlContent = file_get_contents($sqlFile);

try {
    $pdo = getDbConnection();

    // Disable foreign key checks for bulk import
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Split SQL into individual queries
    // specialized split to handle basic ; delimiters but avoid splitting inside strings if possible
    // For this seed file, simple splitting by ;\n or ; at end of line is likely sufficient as it's machine generated

    $queries = explode(";\n", $sqlContent);

    $count = 0;
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $pdo->exec($query);
            $count++;
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<p style='color: green;'>✅ Successfully executed $count queries!</p>";
    echo "<p>Database schema created and seed data imported.</p>";
    echo "<p><a href='test_db.php'>Run Database Test</a></p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
