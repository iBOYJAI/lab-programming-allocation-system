<?php

/**
 * Security Functions
 * Lab Programming Allocation System
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Hash password using bcrypt
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Verify password against hash
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Sanitize input to prevent XSS
 * @param string $input User input
 * @return string Sanitized input
 */
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize output for display
 * @param string $output Data to display
 * @return string Sanitized output
 */
function sanitizeOutput($output)
{
    return htmlspecialchars($output ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function generateCSRFToken()
{
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 * @param string $token Token to validate
 * @return bool True if valid
 */
function validateCSRFToken($token)
{
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Check if session is valid and not expired
 * @return bool True if session is valid
 */
function checkSession()
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
        return false;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        return false;
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Check role-based access
 * @param array $allowed_roles Array of allowed roles
 * @return bool True if user has access
 */
function checkRoleAccess($allowed_roles)
{
    if (!checkSession()) {
        return false;
    }

    return in_array($_SESSION['user_role'], $allowed_roles);
}

/**
 * Log activity to database
 * @param int $user_id User ID
 * @param string $user_role User role (admin/staff/student)
 * @param string $activity_type Type of activity
 * @param string $description Activity description
 * @return bool Success status
 */
function logActivity($user_id, $user_role, $activity_type, $description)
{
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    $sql = "INSERT INTO activity_logs (user_id, user_role, activity_type, description, ip_address) 
            VALUES (?, ?, ?, ?, ?)";

    try {
        return dbExecute($sql, [$user_id, $user_role, $activity_type, $description, $ip_address]);
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a notification for a user
 * @param int|null $user_id Target User ID (null for all admins)
 * @param string $user_role Target User role
 * @param string $title Notification title
 * @param string $message Notification message
 * @param string|null $link Link to redirect when clicked
 * @param string $icon Bootstrap icon class
 * @return bool Success status
 */
function createNotification($user_id, $user_role, $title, $message, $link = null, $icon = 'bi-bell')
{
    $sql = "INSERT INTO notifications (user_id, user_role, title, message, link, icon) 
            VALUES (?, ?, ?, ?, ?, ?)";

    try {
        return dbExecute($sql, [$user_id, $user_role, $title, $message, $link, $icon]);
    } catch (Exception $e) {
        error_log("Notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Start secure session
 */
function startSecureSession()
{
    // Session configuration for security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Strict');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Regenerate session ID on first start
    if (!isset($_SESSION['initiated'])) {
        session_regenerate_id(true);
        $_SESSION['initiated'] = true;
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Destroy session and logout
 */
function logoutUser()
{
    // Log activity before destroying session
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
        logActivity($_SESSION['user_id'], $_SESSION['user_role'], 'logout', 'User logged out');
    }

    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
}

/**
 * Validate email format
 * @param string $email Email address
 * @return bool True if valid
 */
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random password
 * @param int $length Password length
 * @return string Random password
 */
function generateRandomPassword($length = 8)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    $password = '';
    $max = strlen($chars) - 1;

    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }

    return $password;
}

/**
 * Redirect to URL
 * @param string $url URL to redirect to
 */
function redirect($url)
{
    header("Location: " . $url);
    exit;
}

/**
 * Check if user is logged in, redirect if not
 * @param string $redirect_url URL to redirect to if not logged in
 */
function requireLogin($redirect_url = '../index.php')
{
    if (!checkSession()) {
        redirect($redirect_url);
    }
}

/**
 * Check if user is logged in AND has specific role
 * @param array $allowed_roles Array of allowed roles
 * @param string $redirect_url URL to redirect if access denied
 */
function requireRole($allowed_roles, $redirect_url = '../index.php')
{
    if (!checkRoleAccess($allowed_roles)) {
        redirect($redirect_url);
    }
}
