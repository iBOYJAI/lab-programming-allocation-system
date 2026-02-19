<?php

/**
 * View Session Results - Staff Portal
 * Displays results for all students in a specific exam session
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['staff'], '../auth.php');

$session_id = intval($_GET['session_id'] ?? 0);
$staff_id = $_SESSION['user_id'];

if (!$session_id) {
    redirect('exam_history.php');
}

// Fetch Session Details and verify ownership
$session = dbQuery("
    SELECT es.*, s.subject_name, l.lab_name
    FROM exam_sessions es
    INNER JOIN subjects s ON es.subject_id = s.id
    INNER JOIN labs l ON es.lab_id = l.id
    WHERE es.id = ? AND es.staff_id = ?
", [$session_id, $staff_id]);

if (empty($session)) {
    redirect('exam_history.php');
}

$session = $session[0];

// Fetch all student results for this session
$results = dbQuery("
    SELECT sa.*, st.name as student_name, st.roll_number,
           (SELECT marks FROM submissions WHERE allocation_id = sa.id AND question_id = sa.question_1_id ORDER BY submitted_at DESC LIMIT 1) as marks_q1,
           (SELECT marks FROM submissions WHERE allocation_id = sa.id AND question_id = sa.question_2_id ORDER BY submitted_at DESC LIMIT 1) as marks_q2
    FROM student_allocations sa
    INNER JOIN students st ON sa.student_id = st.id
    WHERE sa.session_id = ?
    ORDER BY sa.system_no
", [$session_id]);

$page_title = 'Exam Results';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
                <div class="d-flex align-items-center gap-3">
                    <a href="exam_history.php" class="btn btn-light rounded-circle shadow-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <h4 class="fw-bold mb-0"><?= sanitizeOutput($session['subject_name']) ?> - Results</h4>
                        <p class="text-secondary small mb-0">Code: <?= sanitizeOutput($session['session_code']) ?> • <?= sanitizeOutput($session['lab_name']) ?></p>
                    </div>
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-white border px-3 rounded-pill shadow-sm">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                </div>
            </div>

            <div class="card-notion animate-fade-up delay-100">
                <div class="card-notion-header border-bottom">
                    <h5 class="card-notion-title">Student Performance List</h5>
                </div>
                <div class="card-notion-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Reg No</th>
                                    <th>Student Name</th>
                                    <th class="text-center">Q1 Marks</th>
                                    <th class="text-center">Q2 Marks</th>
                                    <th class="text-center">Total</th>
                                    <th class="pe-4 text-end">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($results)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">No student results available.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($results as $r): ?>
                                        <?php
                                        $m1 = $r['marks_q1'] ?? 0;
                                        $m2 = $r['marks_q2'] ?? 0;
                                        $total = $m1 + $m2;
                                        ?>
                                        <tr>
                                            <td class="ps-4 fw-mono"><?= sanitizeOutput($r['roll_number']) ?></td>
                                            <td><?= sanitizeOutput($r['student_name']) ?></td>
                                            <td class="text-center"><?= $m1 ?></td>
                                            <td class="text-center"><?= $m2 ?></td>
                                            <td class="text-center fw-bold text-primary"><?= $total ?></td>
                                            <td class="pe-4 text-end">
                                                <a href="evaluate.php?allocation_id=<?= $r['id'] ?>" class="btn btn-sm btn-light border rounded-pill">View Submissions</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>