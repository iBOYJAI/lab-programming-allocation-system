<?php

/**
 * Subject Details - Student Portal - Premium Monochrome Redesign
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['student'], '../auth.php');

$subject_id = intval($_GET['id'] ?? 0);
$student_id = $_SESSION['user_id'];

if (!$subject_id) {
    redirect('dashboard.php');
}

$conn = getDbConnection();

// Fetch Subject and Enrollment status
$stmt = $conn->prepare("
    SELECT s.*, ss.enrolled_at 
    FROM subjects s
    JOIN student_subjects ss ON s.id = ss.subject_id
    WHERE s.id = ? AND ss.student_id = ?
");
$stmt->execute([$subject_id, $student_id]);
$subject = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subject) {
    $_SESSION['error'] = "Subject not found or not enrolled";
    redirect('dashboard.php');
}

// Fetch Submission History for this subject
$histStmt = $conn->prepare("
    SELECT sub.*, es.session_code, r.total_marks, es.started_at
    FROM submissions sub
    JOIN student_allocations sa ON sub.allocation_id = sa.id
    JOIN exam_sessions es ON sa.session_id = es.id
    LEFT JOIN results r ON (es.id = r.session_id AND sa.student_id = r.student_id)
    WHERE sa.student_id = ? AND es.subject_id = ?
    ORDER BY sub.submitted_at DESC
");
$histStmt->execute([$student_id, $subject_id]);
$submissions = $histStmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_exams = count($submissions);
$avg_score = 0;
if ($total_exams > 0) {
    $total_p = 0;
    foreach ($submissions as $s) {
        $total_p += ($s['total_marks'] > 0) ? ($s['score'] / $s['total_marks']) * 100 : 0;
    }
    $avg_score = $total_p / $total_exams;
}

$page_title = $subject['subject_name'];
include '../includes/header.php';
?>

<style>
    :root {
        --mono-black: #000000;
        --mono-white: #ffffff;
        --mono-gray: #f2f2f2;
        --mono-border: #e5e5e5;
    }

    .header-mono {
        border-bottom: 2px solid var(--mono-black);
        padding-bottom: 30px;
        margin-bottom: 40px;
    }

    .btn-outline-mono {
        background: transparent;
        color: var(--mono-black);
        border: 1px solid var(--mono-black);
        border-radius: 0;
        font-weight: 600;
        text-transform: uppercase;
        padding: 10px 20px;
        font-size: 0.75rem;
        letter-spacing: 1px;
    }

    .stat-card-mono {
        border: 1px solid var(--mono-border);
        background: var(--mono-white);
        border-radius: 0;
        padding: 25px;
    }

    .badge-mono {
        background: var(--mono-black);
        color: var(--mono-white);
        border-radius: 0;
        padding: 4px 10px;
        text-transform: uppercase;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 1px;
    }

    .table thead th {
        background: var(--mono-gray);
        border-bottom: 2px solid var(--mono-black);
        text-transform: uppercase;
        font-size: 0.7rem;
        letter-spacing: 1px;
        font-weight: 800;
    }
</style>

<div class="app-container">
    <?php include '../includes/sidebar_student.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_student.php'; ?>

        <div class="main-content">
            <div class="header-mono animate-fade-up">
                <div class="row align-items-end">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center gap-4 mb-3">
                            <a href="dashboard.php" class="btn btn-outline-mono p-2 lh-1"><i class="bi bi-arrow-left fs-5"></i></a>
                            <span class="badge-mono">Subject Repository</span>
                        </div>
                        <h1 class="display-5 fw-800 text-uppercase ls-1 mb-0"><?= sanitizeOutput($subject['subject_name']) ?></h1>
                        <p class="text-secondary mt-2 mb-0 fw-mono small"><?= sanitizeOutput($subject['subject_code']) ?> // STACK: <?= sanitizeOutput($subject['language']) ?></p>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5 animate-fade-up">
                <div class="col-md-4">
                    <div class="stat-card-mono">
                        <h6 class="text-uppercase fw-800 opacity-50 small mb-2 ls-1">Assessment Units</h6>
                        <h2 class="fw-800 mb-0"><?= $total_exams ?></h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card-mono">
                        <h6 class="text-uppercase fw-800 opacity-50 small mb-2 ls-1">Aggregate Accuracy</h6>
                        <h2 class="fw-800 mb-0"><?= number_format($avg_score, 1) ?>%</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card-mono">
                        <h6 class="text-uppercase fw-800 opacity-50 small mb-2 ls-1">Initialization</h6>
                        <h2 class="fw-800 mb-0 fs-5"><?= formatDate($subject['enrolled_at'], 'M Y') ?></h2>
                    </div>
                </div>
            </div>

            <div class="animate-fade-up delay-100">
                <h5 class="fw-800 text-uppercase ls-1 mb-4 border-start border-4 border-dark ps-3">Evaluation Logs</h5>
                <div class="card card-notion rounded-0 border-0 shadow-sm overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Reference</th>
                                    <th>Metric Score</th>
                                    <th>Precision</th>
                                    <th class="text-end pe-4">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($submissions)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <p class="text-secondary text-uppercase fw-bold small opacity-50 ls-1">Null History</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($submissions as $s): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <span class="badge-mono"><?= $s['session_code'] ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-800"><?= $s['score'] ?> / <?= $s['total_marks'] ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $p = ($s['total_marks'] > 0) ? ($s['score'] / $s['total_marks']) * 100 : 0;
                                                $color = $p >= 70 ? 'dark' : 'secondary';
                                                ?>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="small fw-bold text-<?= $color ?>"><?= number_format($p, 1) ?>%</span>
                                                    <div class="progress flex-grow-1 bg-light border" style="height: 4px; width: 60px;">
                                                        <div class="progress-bar bg-dark" style="width: <?= $p ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end pe-4 text-muted small"><?= formatDate($s['submitted_at']) ?></td>
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