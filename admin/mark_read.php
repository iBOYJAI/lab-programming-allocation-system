<?php
require_once '../config/database.php';
require_once '../includes/security.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

$admin_id = $_SESSION['user_id'];

$sql = "UPDATE notifications SET is_read = 1 WHERE (user_id = ? AND user_role = 'admin') OR (user_id IS NULL AND user_role = 'admin')";
dbExecute($sql, [$admin_id]);

$referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
redirect($referer);
