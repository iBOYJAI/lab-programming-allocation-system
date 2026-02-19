<?php

/**
 * View Exam Sessions - Enterprise Redesign
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

// Filter
$status = $_GET['status'] ?? 'All';

$query = "
    SELECT es.*, s.subject_name, s.subject_code, st.name as staff_name, l.lab_name
    FROM exam_sessions es
    JOIN subjects s ON es.subject_id = s.id
    LEFT JOIN staff st ON es.staff_id = st.id
    LEFT JOIN labs l ON es.lab_id = l.id
";

$params = [];
if ($status !== 'All') {
    $query .= " WHERE es.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY es.started_at DESC";
$sessions = dbQuery($query, $params);

// Get real-time stats for each session
foreach ($sessions as &$session) {
    if ($session['status'] === 'Active') {
        $stats = dbQuery("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN sa.status_q1 = 'Submitted' OR sa.status_q2 = 'Submitted' THEN 1 ELSE 0 END) as partial_submitted,
                SUM(CASE WHEN sa.status_q1 = 'Submitted' AND sa.status_q2 = 'Submitted' THEN 1 ELSE 0 END) as fully_submitted
            FROM student_allocations sa 
            WHERE sa.session_id = ?
        ", [$session['id']]);

        $res = $stats[0] ?? ['total' => 0, 'partial_submitted' => 0, 'fully_submitted' => 0];
        $session['active_stats'] = [
            'total' => $res['total'],
            'submitted' => $res['fully_submitted']
        ];
    }
}
unset($session);

$page_title = 'Exam Sessions';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title mb-1 text-dark">Exam Sessions</h1>
                    <p class="text-muted small mb-0">Monitor and track ongoing and past examination sessions.</p>
                </div>
                <div class="btn-group bg-white p-1 rounded-pill shadow-sm border">
                    <a href="view_sessions.php?status=All" class="btn btn-sm px-4 rounded-pill fw-bold <?= $status === 'All' ? 'btn-dark' : 'btn-white border-0 text-muted' ?>">All</a>
                    <a href="view_sessions.php?status=Active" class="btn btn-sm px-4 rounded-pill fw-bold <?= $status === 'Active' ? 'btn-success' : 'btn-white border-0 text-muted' ?>">Active</a>
                    <a href="view_sessions.php?status=Completed" class="btn btn-sm px-4 rounded-pill fw-bold <?= $status === 'Completed' ? 'btn-secondary' : 'btn-white border-0 text-muted' ?>">Completed</a>
                </div>
            </div>

            <div class="card card-notion overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table notion-table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Status</th>
                                    <th>Session Details</th>
                                    <th>Location & Type</th>
                                    <th>Supervisor</th>
                                    <th>Time Frame</th>
                                    <th class="pe-4">Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sessions)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <div class="opacity-25 mb-3">
                                                <i class="bi bi-calendar-x display-4 text-muted"></i>
                                            </div>
                                            <p class="fw-bold text-dark mb-1">No matching sessions found.</p>
                                            <p class="small text-muted">Try changing the status filter.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sessions as $session): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <?php if ($session['status'] === 'Active'): ?>
                                                    <span class="badge notion-badge live d-inline-flex align-items-center gap-2">
                                                        <span class="pulse-dot bg-success rounded-circle" style="width: 6px; height: 6px;"></span> ACTIVE
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-secondary border notion-badge">COMPLETED</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-dark mb-1"><?= sanitizeOutput($session['subject_name']) ?></div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <code class="x-small px-2 py-1 bg-indigo-subtle text-indigo rounded fw-bold"><?= sanitizeOutput($session['session_code']) ?></code>
                                                    <span class="text-muted small"><?= sanitizeOutput($session['subject_code']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <div class="small fw-semibold text-dark">
                                                        <i class="bi bi-geo-alt me-1 text-muted"></i><?= sanitizeOutput($session['lab_name'] ?? 'General') ?>
                                                    </div>
                                                    <span class="badge bg-light text-dark border w-auto align-self-start" style="font-size: 0.7rem;"><?= sanitizeOutput($session['test_type']) ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center text-muted small" style="width: 28px; height: 28px;">
                                                        <i class="bi bi-person"></i>
                                                    </div>
                                                    <div class="small fw-bold text-secondary"><?= sanitizeOutput($session['staff_name']) ?: 'Unknown' ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="small text-dark fw-bold"><?= formatDate($session['started_at'], 'd M, H:i') ?></span>
                                                    <small class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-hourglass-split me-1"></i><?= $session['duration_minutes'] ?> mins</small>
                                                </div>
                                            </td>
                                            <td class="pe-4">
                                                <?php if ($session['status'] === 'Active' && isset($session['active_stats'])): ?>
                                                    <?php
                                                    $stats = $session['active_stats'];
                                                    $total = max(1, $stats['total']);
                                                    $pct = round(($stats['submitted'] / $total) * 100);
                                                    ?>
                                                    <div class="d-flex flex-column gap-1" style="min-width: 140px;">
                                                        <div class="d-flex justify-content-between x-small fw-bold text-muted mb-1">
                                                            <span>Submission Rate</span>
                                                            <span class="text-dark"><?= $pct ?>%</span>
                                                        </div>
                                                        <div class="progress rounded-pill bg-light" style="height: 6px;">
                                                            <div class="progress-bar bg-success rounded-pill" style="width: <?= $pct ?>%"></div>
                                                        </div>
                                                        <div class="text-end x-small text-muted mt-1"><?= $stats['submitted'] ?>/<?= $stats['total'] ?> Students</div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic small"><i class="bi bi-check2-all me-1"></i>Session Concluded</span>
                                                <?php endif; ?>
                                                <div class="mt-2">
                                                    <a href="view_session_details.php?id=<?= $session['id'] ?>" class="btn btn-sm btn-white border shadow-sm w-100">View Details</a>
                                                </div>
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

<style>
    .pulse-dot {
        width: 8px;
        height: 8px;
        background-color: #10b981;
        border-radius: 50%;
        display: inline-block;
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
            opacity: 1;
        }

        50% {
            transform: scale(1.5);
            opacity: 0.5;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .x-small {
        font-size: 0.75rem;
    }
</style>

<?php include '../includes/footer.php'; ?>