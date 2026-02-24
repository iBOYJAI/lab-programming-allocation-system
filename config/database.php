<?php

/**
 * Database Configuration File
 * Lab Programming Allocation System
 */

// Database credentials - MODIFY THESE AS PER YOUR SETUP
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Change if different
define('DB_PASS', '');               // Change if you have password
define('DB_NAME', 'lab_allocation_system');
define('DB_CHARSET', 'utf8mb4');

// Set global timezone
date_default_timezone_set('Asia/Kolkata');

// Session configuration
define('SESSION_TIMEOUT', 604800); // 7 days in seconds (7 * 24 * 60 * 60)
define('CSRF_TOKEN_NAME', 'csrf_token');

// File upload configuration
define('MAX_UPLOAD_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['csv', 'txt']);

// Server configuration - AUTO-DETECT
$server_ip = $_SERVER['SERVER_ADDR'] ?? 'localhost';
if ($server_ip === '::1') $server_ip = 'localhost';
define('SERVER_IP', $server_ip);
define('SERVER_PORT', $_SERVER['SERVER_PORT'] ?? '80');
define('BASE_URL', 'http://' . SERVER_IP . (SERVER_PORT != '80' ? ':' . SERVER_PORT : ''));

// Error reporting - Set to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Get database connection using PDO
 * @return PDO Database connection object
 * @throws PDOException if connection fails
 */
function getDbConnection()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error instead of displaying in production
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please check configuration.");
        }
    }

    return $pdo;
}

/**
 * Execute a query and return results
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters for prepared statement
 * @return array Query results
 */
function dbQuery($sql, $params = [])
{
    $sql = trim($sql);
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
        throw new Exception("Database query failed: " . $e->getMessage());
    }
}

/**
 * Execute INSERT/UPDATE/DELETE query
 * @param string $sql SQL query with placeholders
 * @param array $params Parameters for prepared statement
 * @return bool Success status
 */
function dbExecute($sql, $params = [])
{
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Execute Error: " . $e->getMessage() . " | SQL: " . $sql);
        throw new Exception("Database operation failed: " . $e->getMessage());
    }
}

/**
 * Get last inserted ID
 * @return int Last insert ID
 */
function dbLastInsertId()
{
    $pdo = getDbConnection();
    return $pdo->lastInsertId();
}

// Test connection on first include (optional, comment out in production)
// getDbConnection();
