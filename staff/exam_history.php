<?php

/**
 * Exam History - Staff Portal
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['staff'], '../auth.php');

$staff_id = $_SESSION['user_id'];

// Get past sessions by this staff
$past_sessions = dbQuery("
    SELECT es.*, s.subject_name, l.lab_name,
           (SELECT COUNT(*) FROM student_allocations WHERE session_id = es.id) as student_count,
           (SELECT COUNT(*) FROM student_allocations WHERE session_id = es.id AND status_q1 = 'Submitted' AND status_q2 = 'Submitted') as completed_students
    FROM exam_sessions es
    INNER JOIN subjects s ON es.subject_id = s.id
    INNER JOIN labs l ON es.lab_id = l.id
    WHERE es.staff_id = ? AND es.status = 'Completed'
    ORDER BY es.started_at DESC
", [$staff_id]);

$page_title = 'Exam History';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <div class="d-flex align-items-center gap-3 mb-4 animate-fade-up">
                <div class="bg-indigo-subtle p-2 rounded-3 text-indigo">
                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-calendar-square.svg" width="24" class="notion-icon">
                </div>
                <div>
                    <h4 class="fw-bold mb-0">Exam History</h4>
                    <p class="text-secondary small mb-0">View past examination sessions and results.</p>
                </div>
            </div>

            <div class="card-notion animate-fade-up delay-100">
                <div class="card-notion-header border-bottom">
                    <h5 class="card-notion-title">
                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-folders.svg" width="18" class="opacity-50">
                        Past Sessions
                    </h5>
                </div>
                <div class="card-notion-body p-0">
                    <?php if (empty($past_sessions)): ?>
                        <div class="text-center py-5">
                            <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-folders.svg" width="64" class="opacity-25 mb-3">
                            <h5 class="fw-bold text-dark">No Past Exams Found</h5>
                            <p class="text-muted">You haven't conducted any exams yet or exams are still active.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 py-3 text-secondary text-uppercase small fw-bold">Date</th>
                                        <th class="py-3 text-secondary text-uppercase small fw-bold">Subject</th>
                                        <th class="py-3 text-secondary text-uppercase small fw-bold">Lab</th>
                                        <th class="py-3 text-secondary text-uppercase small fw-bold">Code</th>
                                        <th class="py-3 text-secondary text-uppercase small fw-bold text-center">Students</th>
                                        <th class="pe-4 py-3 text-end text-secondary text-uppercase small fw-bold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($past_sessions as $session): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= date('M d, Y', strtotime($session['started_at'])) ?></div>
                                                <small class="text-muted"><?= date('h:i A', strtotime($session['started_at'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark"><?= sanitizeOutput($session['subject_name']) ?></div>
                                                <small class="text-muted text-uppercase small"><?= sanitizeOutput($session['test_type']) ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-geo-alt text-secondary small"></i>
                                                    <span><?= sanitizeOutput($session['lab_name']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <code class="bg-light px-2 py-1 rounded text-primary"><?= sanitizeOutput($session['session_code']) ?></code>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark border fw-medium"><?= $session['completed_students'] ?> / <?= $session['student_count'] ?></span>
                                            </td>
                                            <td class="pe-4 text-end">
                                                <a href="view_result.php?session_id=<?= $session['id'] ?>" class="btn btn-sm btn-white border px-3 rounded-pill shadow-sm">
                                                    <i class="bi bi-eye me-1"></i> Results
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>