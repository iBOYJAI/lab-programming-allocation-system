<?php

/**
 * Reports & Analytics - Staff Portal
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['staff'], '../auth.php');

$staff_id = $_SESSION['user_id'];

// Get overall stats for staff's subjects
$stats = dbQuery("
    SELECT 
        COUNT(DISTINCT es.id) as total_exams,
        (SELECT COUNT(*) FROM student_allocations sa INNER JOIN exam_sessions es2 ON sa.session_id = es2.id WHERE es2.staff_id = ?) as total_students,
        (SELECT AVG(marks) FROM submissions s INNER JOIN student_allocations sa2 ON s.allocation_id = sa2.id INNER JOIN exam_sessions es3 ON sa2.session_id = es3.id WHERE es3.staff_id = ?) as avg_score
    FROM exam_sessions es
    WHERE es.staff_id = ?
", [$staff_id, $staff_id, $staff_id])[0];

$page_title = 'Reports & Analytics';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <div class="d-flex align-items-center gap-3 mb-4 animate-fade-up">
                <div class="bg-indigo-subtle p-2 rounded-3 text-indigo">
                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-clipboard-bar-chart.svg" width="24" class="notion-icon">
                </div>
                <div>
                    <h4 class="fw-bold mb-0">Reports & Analytics</h4>
                    <p class="text-secondary small mb-0">Overview of student performance and trends.</p>
                </div>
            </div>

            <div class="row g-4 mb-4 animate-fade-up delay-100">
                <div class="col-md-4">
                    <div class="notion-stat-card">
                        <div class="notion-stat-label">Total Conducted</div>
                        <div class="notion-stat-value"><?= $stats['total_exams'] ?></div>
                        <div class="small text-muted mt-2">Sessions conducted so far</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="notion-stat-card">
                        <div class="notion-stat-label">Total Participants</div>
                        <div class="notion-stat-value"><?= $stats['total_students'] ?></div>
                        <div class="small text-muted mt-2">Across all sessions</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="notion-stat-card">
                        <div class="notion-stat-label">Class Average</div>
                        <div class="notion-stat-value"><?= round($stats['avg_score'] ?? 0, 1) ?>%</div>
                        <div class="small text-muted mt-2">Historical performance</div>
                    </div>
                </div>
            </div>

            <div class="card-notion animate-fade-up delay-200">
                <div class="card-notion-body text-center py-5">
                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-exclamation-triangle.svg" width="64" class="opacity-25 mb-3">
                    <h5 class="fw-bold text-dark">Analytics Dashboard Under Development</h5>
                    <p class="text-muted">Detailed visual charts and trend analysis are coming soon.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>