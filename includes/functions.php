<?php

/**
 * Common Utility Functions
 * Lab Programming Allocation System
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Format date for display
 * @param string $date Date string or timestamp
 * @param string $format Date format
 * @return string Formatted date
 */
function formatDate($date, $format = 'd-m-Y H:i:s')
{
    if (empty($date)) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Get time elapsed string
 * @param string $datetime Datetime string
 * @return string Elapsed time (e.g., "5 minutes ago")
 */
function getTimeElapsed($datetime)
{
    $timestamp = strtotime($datetime);
    $current = time();
    $diff = $current - $timestamp;

    // Handle potential future dates or small clock drifts
    if ($diff < 5) {
        return 'Just now';
    }

    // Capture absolute difference for formatting
    $abs_diff = abs($diff);

    if ($abs_diff < 60) {
        return $abs_diff . ' seconds ago';
    } elseif ($abs_diff < 3600) {
        return floor($abs_diff / 60) . ' minutes ago';
    } elseif ($abs_diff < 86400) {
        return floor($abs_diff / 3600) . ' hours ago';
    } elseif ($abs_diff < 604800) {
        return floor($abs_diff / 86400) . ' days ago';
    } else {
        return formatDate($datetime, 'd M Y');
    }
}

/**
 * Generate unique session code
 * @param string $prefix Prefix for session code
 * @return string Unique session code
 */
function generateSessionCode($prefix = 'EXAM')
{
    return $prefix . '_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 6);
}

/**
 * Generate unique staff ID
 * @return string Unique staff ID
 */
function generateStaffId()
{
    $pdo = getDbConnection();

    // Get last staff ID
    $stmt = $pdo->query("SELECT staff_id FROM staff ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetch();

    if ($last) {
        $num = intval(substr($last['staff_id'], 5)) + 1;
    } else {
        $num = 1;
    }

    return 'STAFF' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

/**
 * Validate CSV file
 * @param array $file Uploaded file from $_FILES
 * @return string|bool Error message or true if valid
 */
function validateCSVFile($file)
{
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return "No file uploaded";
    }

    // Check file size
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return "File size exceeds 5MB limit";
    }

    // Check file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return "Invalid file type. Only CSV files allowed";
    }

    return true;
}

/**
 * Parse CSV file
 * @param string $filepath Path to CSV file
 * @return array Array of rows
 */
function parseCSV($filepath)
{
    $rows = [];

    if (($handle = fopen($filepath, 'r')) !== false) {
        $header = fgetcsv($handle); // Skip header row

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) > 0 && !empty($data[0])) {
                $rows[] = $data;
            }
        }

        fclose($handle);
    }

    return $rows;
}

/**
 * Get status badge HTML
 * @param string $status Status text
 * @return string HTML for badge
 */
function getStatusBadge($status)
{
    $classes = [
        'Not Started' => 'bg-secondary',
        'In Progress' => 'bg-warning',
        'Submitted' => 'bg-success',
        'Active' => 'bg-success',
        'Completed' => 'bg-primary',
        'Cancelled' => 'bg-danger'
    ];

    $class = $classes[$status] ?? 'bg-secondary';
    return '<span class="badge ' . $class . '">' . sanitizeOutput($status) . '</span>';
}


/**
 * Get pagination HTML
 * @param int $total_records Total number of records
 * @param int $per_page Records per page
 * @param int $current_page Current page number
 * @param string $base_url Base URL for pagination links
 * @return string HTML for pagination
 */
function getPagination($total_records, $per_page, $current_page, $base_url)
{
    $total_pages = ceil($total_records / $per_page);

    if ($total_pages <= 1) return '';

    $html = '<nav><ul class="pagination justify-content-center">';

    // Previous button
    if ($current_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page - 1) . '">Previous</a></li>';
    }

    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base_url . '&page=' . $i . '">' . $i . '</a></li>';
    }

    // Next button
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '&page=' . ($current_page + 1) . '">Next</a></li>';
    }

    $html .= '</ul></nav>';

    return $html;
}

/**
 * Show success alert
 * @param string $message Success message
 * @return string HTML for alert
 */
function showSuccess($message)
{
    return '<div class="alert alert-success alert-dismissible fade show" role="alert">
                ' . sanitizeOutput($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

/**
 * Show error alert
 * @param string $message Error message
 * @return string HTML for alert
 */
function showError($message)
{
    return '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                ' . sanitizeOutput($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

/**
 * Show info alert
 * @param string $message Info message
 * @return string HTML for alert
 */
function showInfo($message)
{
    return '<div class="alert alert-info alert-dismissible fade show" role="alert">
                ' . sanitizeOutput($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

/**
 * Get language syntax mode for CodeMirror
 * @param string $language Programming language
 * @return string CodeMirror mode
 */
function getCodeMirrorMode($language)
{
    $modes = [
        'C' => 'text/x-csrc',
        'C++' => 'text/x-c++src',
        'Java' => 'text/x-java',
        'Python' => 'text/x-python'
    ];

    return $modes[$language] ?? 'text/plain';
}

/**
 * Truncate text to specified length
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $suffix Suffix to append
 * @return string Truncated text
 */
function truncateText($text, $length = 50, $suffix = '...')
{
    $text = $text ?? '';
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . $suffix;
}

/**
 * Export data to CSV
 * @param array $data Data to export
 * @param array $headers Column headers
 * @param string $filename Filename
 */
function exportToCSV($data, $headers, $filename)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');

    // Write headers
    fputcsv($output, $headers);

    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

/**
 * Get active session for student
 * @param int $student_id Student ID
 * @return array|null Session data or null
 */
function getActiveSessionForStudent($student_id)
{
    $sql = "SELECT es.*, s.subject_name, s.language, sa.question_1_id, sa.question_2_id, sa.id as allocation_id
            FROM exam_sessions es
            INNER JOIN student_allocations sa ON es.id = sa.session_id
            INNER JOIN subjects s ON es.subject_id = s.id
            WHERE sa.student_id = ? AND es.status = 'Active'
            LIMIT 1";

    $result = dbQuery($sql, [$student_id]);
    return !empty($result) ? $result[0] : null;
}

/**
 * Calculate time remaining in exam
 * @param string $started_at Exam start time
 * @param int $duration_minutes Exam duration
 * @return int|null Minutes remaining or null if no limit
 */
function getTimeRemaining($started_at, $duration_minutes)
{
    if ($duration_minutes == 0) return null;

    $start = strtotime($started_at);
    $end = $start + ($duration_minutes * 60);
    $remaining = $end - time();

    return max(0, ceil($remaining / 60));
}

/**
 * Get profile image path based on gender and seed or explicit avatar ID
 * @param string $gender 'Male' or 'Female'
 * @param int|string $seed Unique identifier to ensure consistent avatar for same user (fallback)
 * @param int|null $avatar_id Explicitly selected avatar ID (1-7 for boys, 1-4 for girls)
 * @return string Path to avatar image
 */
function getProfileImage($gender, $seed, $avatar_id = null)
{
    // Normalize input
    $gender = ucfirst(strtolower($gender ?? 'Male'));

    // Avatar configuration based on existing files in assets/images/Avatar/
    // Boys: boy-1.png through boy-7.png
    // Girls: girl-1.png through girl-4.png

    if ($gender === 'Female') {
        $max_avatars = 4;
        $prefix = 'girl';
    } else {
        $max_avatars = 7; // Default to Male avatars
        $prefix = 'boy';
    }

    // 1. If explicit avatar_id is set and valid, use it
    if ($avatar_id && $avatar_id >= 1 && $avatar_id <= $max_avatars) {
        return "$prefix-$avatar_id.png";
    }

    // 2. Fallback: Select avatar based on seed (deterministic hash)
    $seed_int = crc32((string)$seed);
    $avatar_num = (abs($seed_int) % $max_avatars) + 1; // 1-indexed

    return "$prefix-$avatar_num.png";
}

/**
 * Display alert messages from session
 * @return string HTML for alert
 */
function displayAlert()
{
    $html = '';
    if (isset($_SESSION['success'])) {
        $html = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>' . sanitizeOutput($_SESSION['success']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                 </div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        $html = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>' . sanitizeOutput($_SESSION['error']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                 </div>';
        unset($_SESSION['error']);
    }
    return $html;
}
/**
 * Generate a random 6-digit submission key
 * @return string 6-digit numeric key
 */
function generateSubmissionKey()
{
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Rotate or fetch current submission key for a session
 * @param int $session_id Session ID
 * @param bool $force_rotate Force a new key generation
 * @return string Current or new key
 */
function rotateSessionKey($session_id, $force_rotate = false)
{
    $pdo = getDbConnection();

    // Check current key, its age, and session status
    $stmt = $pdo->prepare("SELECT current_submission_key, key_updated_at, status FROM exam_sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch();

    if (!$session) return '000000';

    // If session is NOT active, do not rotate, just return current (or placeholder)
    if ($session['status'] !== 'Active') {
        return $session['current_submission_key'] ?? 'EXPIRED';
    }

    $now = time();
    $last_update = $session['key_updated_at'] ? strtotime($session['key_updated_at']) : 0;

    // Rotate every 60 seconds or if forced or if no key exists
    if ($force_rotate || !$session['current_submission_key'] || ($now - $last_update) >= 60) {
        $new_key = generateSubmissionKey();
        $stmt = $pdo->prepare("UPDATE exam_sessions SET current_submission_key = ?, key_updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_key, $session_id]);
        return $new_key;
    }

    return $session['current_submission_key'];
}
