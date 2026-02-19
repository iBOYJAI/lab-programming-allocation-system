<?php

/**
 * Database Connection & Auth Test
 * Quick verification script
 */

require_once __DIR__ . '/../config/database.php';

echo "<h2>Database Connection Test</h2>";

try {
    $pdo = getDbConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";

    echo "<h3>Testing Admin Credentials</h3>";
    $sql = "SELECT * FROM users WHERE username = 'srinath'";
    $result = dbQuery($sql, []);

    if (!empty($result)) {
        $admin = $result[0];
        echo "<p>✅ Admin user found!</p>";
        echo "<p>Username: " . $admin['username'] . "</p>";
        echo "<p>Password Hash: " . substr($admin['password'], 0, 30) . "...</p>";

        // Test password verification
        $test_password = 'Admin@123';
        if (password_verify($test_password, $admin['password'])) {
            echo "<p style='color: green;'>✅ Password 'Admin@123' is CORRECT!</p>";
        } else {
            echo "<p style='color: red;'>❌ Password 'Admin@123' does NOT match!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Admin user NOT found in database!</p>";
    }

    echo "<h3>Testing Staff Credentials</h3>";
    $sql = "SELECT * FROM staff WHERE staff_id = 'STF100' LIMIT 1";
    $result = dbQuery($sql, []);

    if (!empty($result)) {
        $staff = $result[0];
        echo "<p>✅ Staff member found!</p>";
        echo "<p>Staff ID: " . $staff['staff_id'] . "</p>";
        echo "<p>Name: " . $staff['name'] . "</p>";

        if (password_verify('Admin@123', $staff['password'])) {
            echo "<p style='color: green;'>✅ Staff password is CORRECT!</p>";
        } else {
            echo "<p style='color: red;'>❌ Staff password does NOT match!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Staff member NOT found!</p>";
    }

    echo "<h3>Testing Student Credentials</h3>";
    $sql = "SELECT * FROM students WHERE roll_number = '23-CS-001' LIMIT 1";
    $result = dbQuery($sql, []);

    if (!empty($result)) {
        $student = $result[0];
        echo "<p>✅ Student found!</p>";
        echo "<p>Roll No: " . $student['roll_number'] . "</p>";
        echo "<p>Name: " . $student['name'] . "</p>";

        if (password_verify('Password@123', $student['password'])) {
            echo "<p style='color: green;'>✅ Student password is CORRECT!</p>";
        } else {
            echo "<p style='color: red;'>❌ Student password does NOT match!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Student NOT found!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Database Configuration</h3>";
echo "<p>Host: " . DB_HOST . "</p>";
echo "<p>Database: " . DB_NAME . "</p>";
echo "<p>User: " . DB_USER . "</p>";

echo "<hr>";
echo "<p><a href='index.php'>Back to Home</a> | <a href='auth.php'>Go to Login</a></p>";
