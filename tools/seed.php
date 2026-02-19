<?php

/**
 * Database Seeder Script
 * Lab Programming Allocation System
 * 
 * This script ensures the database has the correct tables and 
 * updates the default passwords to "admin123" properly.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

header('Content-Type: text/plain');

echo "========================================\n";
echo "   LAB ALLOCATION SYSTEM - SEEDER       \n";
echo "========================================\n\n";

try {
    $pdo = getDbConnection();
    echo "[+] Database connection successful!\n";

    // 1. Check if tables exist by trying to select from 'users'
    try {
        $pdo->query("SELECT 1 FROM users LIMIT 1");
        echo "[+] Database tables already exist.\n";
    } catch (PDOException $e) {
        echo "[!] Tables not found. Importing schema...\n";
        $sql = file_get_contents(__DIR__ . '/lab_allocation_system.sql');
        $pdo->exec($sql);
        echo "[+] Schema imported successfully.\n";
    }

    // 2. Generate correct hash for 'admin123'
    $newHash = password_hash('admin123', PASSWORD_BCRYPT);
    echo "[+] Generated hash for 'admin123': $newHash\n";

    // 3. Update Admin password
    echo "[*] Updating Admin password...\n";
    dbExecute("UPDATE users SET password = ? WHERE username = 'admin'", [$newHash]);

    // 4. Update Staff passwords
    echo "[*] Updating Staff passwords...\n";
    dbExecute("UPDATE staff SET password = ?", [$newHash]);

    // 5. Update Student passwords
    echo "[*] Updating Student passwords...\n";
    dbExecute("UPDATE students SET password = ?", [$newHash]);

    echo "\n========================================\n";
    echo "✅ SEEDING COMPLETED SUCCESSFULLY!      \n";
    echo "========================================\n";
    echo "Login Credentials (PASSWORD: admin123)  \n";
    echo "----------------------------------------\n";
    echo "Admin: admin\n";
    echo "Staff: STAFF001\n";
    echo "Student: 2024CS001\n";
    echo "========================================\n";
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}
