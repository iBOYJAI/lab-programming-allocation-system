<?php

/**
 * Staff Dashboard - Premium Redesign
 * Lab Programming Allocation System
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['staff'], '../auth.php');

$staff_db_id = $_SESSION['user_id'];

// Get active sessions by this staff
$active_sessions = dbQuery("
    SELECT es.*, s.subject_name, l.lab_name,
           (SELECT COUNT(*) FROM student_allocations WHERE session_id = es.id) as student_count,
           (SELECT COUNT(*) FROM student_allocations WHERE session_id = es.id AND status_q1 = 'Submitted' AND status_q2 = 'Submitted') as completed_students
    FROM exam_sessions es
    INNER JOIN subjects s ON es.subject_id = s.id
    INNER JOIN labs l ON es.lab_id = l.id
    WHERE es.staff_id = ? AND es.status = 'Active'
    ORDER BY es.started_at DESC
", [$staff_db_id]);

// Get assigned labs & subjects
$assigned = dbQuery("
    SELECT s.*, l.lab_name
    FROM staff_assignments sa
    INNER JOIN subjects s ON sa.subject_id = s.id
    INNER JOIN labs l ON sa.lab_id = l.id
    WHERE sa.staff_id = ?
", [$staff_db_id]);

// Stats for staff
$stats_counts = [
    'active' => count($active_sessions),
    'assigned' => count($assigned),
    'completed' => dbQuery("SELECT COUNT(*) as count FROM exam_sessions WHERE staff_id = ? AND status = 'Completed'", [$staff_db_id])[0]['count']
];

$page_title = 'Staff Dashboard';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <!-- Welcome Section -->
            <div class="admin-hero animate-fade-up">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-briefcase.svg" width="28" class="opacity-75">
                            <h1 class="fw-bold mb-0 text-dark fs-3">Staff Console</h1>
                        </div>
                        <p class="mb-0 text-muted ps-1">Manage examinations and evaluate student performance efficiently.</p>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Stats Grid -->
                <?php
                $stat_items = [
                    ['label' => 'Active Sessions', 'value' => $stats_counts['active'], 'icon' => 'ni-gauge', 'color' => 'blue', 'trend' => 'Live now'],
                    ['label' => 'Assigned Subjects', 'value' => $stats_counts['assigned'], 'icon' => 'ni-collection', 'color' => 'indigo', 'trend' => 'My Workload'],
                    ['label' => 'Completed Exams', 'value' => $stats_counts['completed'], 'icon' => 'ni-ok', 'color' => 'green', 'trend' => 'Total'],
                ];
                foreach ($stat_items as $index => $item): ?>
                    <div class="col-md-4">
                        <div class="notion-stat-card animate-fade-up delay-<?= ($index + 1) * 100 ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="notion-stat-icon-wrapper <?= $item['color'] ?>">
                                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/<?= $item['icon'] ?>.svg" width="24">
                                </div>
                                <span class="notion-stat-trend <?= $item['color'] === 'blue' ? 'positive' : 'neutral' ?>">
                                    <?= $item['trend'] ?>
                                </span>
                            </div>
                            <div class="mt-auto">
                                <div class="notion-stat-label"><?= $item['label'] ?></div>
                                <div class="notion-stat-value"><?= number_format($item['value']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-4">
                <!-- Active Exams Monitoring -->
                <div class="col-lg-8">
                    <div class="card-notion h-100 animate-fade-up delay-100">
                        <div class="card-notion-header">
                            <h5 class="card-notion-title">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-browser.svg" width="18" class="opacity-50">
                                Live Monitoring Hub
                            </h5>
                            <?php if (!empty($active_sessions)): ?>
                                <span class="notion-badge live">LIVE</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-notion-body">
                            <?php if (empty($active_sessions)): ?>
                                <div class="text-center py-5">
                                    <div class="opacity-25 mb-3">
                                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-clipboard-list-check.svg" width="64">
                                    </div>
                                    <h5 class="text-dark fw-bold mb-2">No Active Exams</h5>
                                    <p class="text-muted mb-4 opacity-75">You don't have any ongoing exam sessions to monitor.</p>
                                    <a href="start_exam.php" class="btn btn-primary d-inline-flex align-items-center gap-2 rounded-pill px-4">
                                        <i class="bi bi-play-fill"></i> Start New Exam
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($active_sessions as $session): ?>
                                        <?php
                                        $total = max(1, $session['student_count']);
                                        $pct = round(($session['completed_students'] / $total) * 100);
                                        ?>
                                        <div class="col-md-12">
                                            <div class="p-4 bg-light border rounded-3 position-relative overflow-hidden hover-shadow-sm transition-all text-decoration-none">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div>
                                                        <h6 class="text-primary fw-bold mb-1 fs-5"><?= sanitizeOutput($session['subject_name']) ?></h6>
                                                        <div class="d-flex gap-3 align-items-center mt-2">
                                                            <span class="text-secondary small d-flex align-items-center gap-1 bg-white px-2 py-1 rounded border">
                                                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-truck-location.svg" width="14" class="opacity-60"> <?= sanitizeOutput($session['lab_name']) ?>
                                                            </span>
                                                            <span class="text-secondary small d-flex align-items-center gap-1 bg-white px-2 py-1 rounded border">
                                                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-list.svg" width="14" class="opacity-60"> <?= sanitizeOutput($session['session_code']) ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="fw-bold text-dark fs-3 lh-1"><?= $session['student_count'] ?></div>
                                                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.5px;">Students</small>
                                                    </div>
                                                </div>

                                                <div class="my-3">
                                                    <div class="d-flex justify-content-between small mb-1">
                                                        <span class="text-muted">Completion Progress</span>
                                                        <span class="fw-bold"><?= $pct ?>%</span>
                                                    </div>
                                                    <div class="progress" style="height: 6px; border-radius: 3px;">
                                                        <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-3">
                                                    <div class="text-muted small d-flex align-items-center gap-2">
                                                        <div class="spinner-grow spinner-grow-sm text-danger" role="status" style="width: 0.5rem; height: 0.5rem;"></div>
                                                        Running for: <span class="fw-medium text-dark"><?= getTimeElapsed($session['started_at']) ?></span>
                                                    </div>
                                                    <a href="monitor_exam.php?session_id=<?= $session['id'] ?>" class="btn btn-warning btn-sm fw-bold px-4 text-white rounded-pill shadow-sm">
                                                        MONITOR LIVE <i class="bi bi-arrow-right ms-1"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Assigned Subjects -->
                <div class="col-lg-4">
                    <div class="card-notion animate-fade-up delay-200 h-100">
                        <div class="card-notion-header">
                            <h5 class="card-notion-title">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-collection.svg" width="18" class="opacity-50">
                                Assigned Subjects
                            </h5>
                        </div>
                        <div class="p-0">
                            <div class="list-group list-group-flush">
                                <?php if (empty($assigned)): ?>
                                    <div class="p-5 text-center text-muted">
                                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-file-text.svg" width="32" class="opacity-25 mb-2">
                                        <p class="mb-0">No subjects assigned.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($assigned as $entry): ?>
                                        <div class="list-group-item d-flex align-items-center gap-3 py-3 px-4 border-bottom hover-bg-light transition-all">
                                            <div class="flex-shrink-0 bg-indigo-subtle p-2 rounded-3 text-indigo">
                                                <i class="bi bi-book fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold text-dark mb-0 font-size-sm"><?= sanitizeOutput($entry['subject_name']) ?></div>
                                                <small class="text-muted font-size-xs d-flex align-items-center gap-1">
                                                    <i class="bi bi-display"></i> <?= sanitizeOutput($entry['lab_name']) ?>
                                                </small>
                                            </div>
                                            <a href="subject_details.php?id=<?= $entry['id'] ?>" class="btn btn-sm btn-icon btn-ghost-secondary rounded-circle">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>