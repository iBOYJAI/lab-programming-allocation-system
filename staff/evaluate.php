<?php

/**
 * Evaluate Submissions - Staff Portal
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['staff'], '../auth.php');

$staff_id = $_SESSION['user_id'];

// Get pending/recent submissions for evaluation
$submissions = dbQuery("
    SELECT s.*, st.roll_number, st.name as student_name, sub.subject_name,
           (50.00) as total_marks
    FROM submissions s
    INNER JOIN student_allocations sa ON s.allocation_id = sa.id
    INNER JOIN students st ON sa.student_id = st.id
    INNER JOIN exam_sessions es ON sa.session_id = es.id
    INNER JOIN subjects sub ON es.subject_id = sub.id
    WHERE es.staff_id = ?
    ORDER BY s.submitted_at DESC
    LIMIT 10
", [$staff_id]);
$page_title = 'Evaluate Submissions';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <div class="d-flex align-items-center gap-3 mb-4 animate-fade-up">
                <div class="bg-indigo-subtle p-2 rounded-3 text-indigo">
                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-list-check-square.svg" width="24" class="notion-icon">
                </div>
                <div>
                    <h4 class="fw-bold mb-0">Evaluate Submissions</h4>
                    <p class="text-secondary small mb-0">Review and grade student code submissions.</p>
                </div>
            </div>

            <div class="card-notion animate-fade-up delay-100">
                <div class="card-notion-header border-bottom">
                    <h5 class="card-notion-title">
                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-clipboard-check.svg" width="18" class="opacity-50">
                        Recent Submissions
                    </h5>
                </div>
                <div class="card-notion-body p-0">
                    <?php if (empty($submissions)): ?>
                        <div class="text-center py-5">
                            <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-exclamation-triangle.svg" width="64" class="opacity-25 mb-3">
                            <h5 class="fw-bold text-dark">No Submissions Found</h5>
                            <p class="text-muted">No students have submitted code yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Student</th>
                                        <th>Subject</th>
                                        <th>Question</th>
                                        <th>Score</th>
                                        <th class="pe-4 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $sub): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold"><?= sanitizeOutput($sub['student_name']) ?></div>
                                                <small class="text-muted"><?= sanitizeOutput($sub['roll_number']) ?></small>
                                            </td>
                                            <td><?= sanitizeOutput($sub['subject_name']) ?></td>
                                            <td>Task #<?= $sub['question_id'] ?></td>
                                            <td><span class="badge bg-success-subtle text-success"><?= $sub['marks'] ?? '0' ?>/<?= $sub['total_marks'] ?></span></td>
                                            <td class="pe-4 text-end">
                                                <a href="view_submission.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-light border rounded-pill">View Code</a>
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