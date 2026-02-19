<?php

/**
 * View Session Details - Premium Design
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

if (!isset($_GET['id'])) {
    header("Location: view_sessions.php");
    exit;
}

$session_id = intval($_GET['id']);
$conn = getDbConnection();

// Fetch Session Details
$stmt = $conn->prepare("
    SELECT es.*, s.subject_name, s.subject_code, l.lab_name, st.name as staff_name
    FROM exam_sessions es
    JOIN subjects s ON es.subject_id = s.id
    LEFT JOIN labs l ON es.lab_id = l.id
    LEFT JOIN staff st ON es.staff_id = st.id
    WHERE es.id = ?
");
$stmt->execute([$session_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    $_SESSION['error'] = "Session not found.";
    header("Location: view_sessions.php");
    exit;
}

// Fetch Student Allocations for this session
$allocSql = "
    SELECT sa.*, stu.name as student_name, stu.roll_number, stu.avatar_id, stu.gender
    FROM student_allocations sa
    JOIN students stu ON sa.student_id = stu.id
    WHERE sa.session_id = ?
    ORDER BY stu.name
";
$allocStmt = $conn->prepare($allocSql);
$allocStmt->execute([$session_id]);
$allocations = $allocStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats
$total_students = count($allocations);
$submitted_count = 0;
foreach ($allocations as $a) {
    if ($a['status_q1'] === 'Submitted' && $a['status_q2'] === 'Submitted') {
        $submitted_count++;
    }
}
$completion_rate = $total_students > 0 ? round(($submitted_count / $total_students) * 100) : 0;

$page_title = 'Session Details';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-3">
                    <a href="view_sessions.php" class="btn btn-light rounded-circle shadow-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h1 class="fw-bold mb-0 text-dark fs-3">Session <?= sanitizeOutput($session['session_code']) ?></h1>
                            <?php if ($session['status'] === 'Active'): ?>
                                <span class="badge bg-success-subtle text-success border px-2">Active</span>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border px-2">Completed</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-secondary mb-0">
                            <?= sanitizeOutput($session['subject_name']) ?> (<?= sanitizeOutput($session['subject_code']) ?>) •
                            <?= date('M d, Y', strtotime($session['started_at'])) ?>
                        </p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-white border shadow-sm rounded-pill fw-bold" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i>Report
                    </button>
                </div>
            </div>

            <!-- Stats/Info Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card card-notion h-100">
                        <div class="card-notion-body p-3">
                            <label class="small text-muted fw-bold text-uppercase mb-1">Total Candidates</label>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-people text-primary fs-4"></i>
                                <span class="fs-4 fw-bold text-dark"><?= $total_students ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-notion h-100">
                        <div class="card-notion-body p-3">
                            <label class="small text-muted fw-bold text-uppercase mb-1">Completion</label>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-pie-chart text-success fs-4"></i>
                                <span class="fs-4 fw-bold text-dark"><?= $completion_rate ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-notion h-100">
                        <div class="card-notion-body p-3">
                            <label class="small text-muted fw-bold text-uppercase mb-1">Lab Venue</label>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-pc-display text-indigo fs-4"></i>
                                <span class="fs-5 fw-bold text-dark text-truncate"><?= sanitizeOutput($session['lab_name']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-notion h-100">
                        <div class="card-notion-body p-3">
                            <label class="small text-muted fw-bold text-uppercase mb-1">Supervisor</label>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-person-badge text-warning fs-4"></i>
                                <span class="fs-5 fw-bold text-dark text-truncate"><?= sanitizeOutput($session['staff_name']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Allocations Table -->
            <div class="card card-notion">
                <div class="card-header bg-transparent border-bottom p-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Student Allocations</h5>
                    <div class="input-group w-auto">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" id="studentSearch" class="form-control border-start-0" placeholder="Filter students..." style="max-width: 200px;">
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table notion-table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Student</th>
                                    <th>System IP</th>
                                    <th>Q1 Status</th>
                                    <th>Q2 Status</th>
                                    <th class="pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($allocations)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">No students allocated to this session.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allocations as $alloc):
                                        $avatar = getProfileImage($alloc['gender'], $alloc['student_id'], $alloc['avatar_id'] ?? null);
                                    ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="../assets/images/Avatar/<?= $avatar ?>" class="rounded-circle" width="32" height="32">
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= sanitizeOutput($alloc['student_name']) ?></div>
                                                        <div class="small text-muted"><?= sanitizeOutput($alloc['roll_number']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="fw-mono small bg-light px-2 py-1 rounded"><?= $alloc['system_ip'] ?? 'Pending' ?></span></td>
                                            <td>
                                                <?php
                                                $s1 = $alloc['status_q1'];
                                                $badge1 = ($s1 === 'Submitted') ? 'bg-success-subtle text-success' : (($s1 === 'In Progress') ? 'bg-warning-subtle text-warning' : 'bg-light text-muted');
                                                ?>
                                                <span class="badge <?= $badge1 ?> border"><?= $s1 ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $s2 = $alloc['status_q2'];
                                                $badge2 = ($s2 === 'Submitted') ? 'bg-success-subtle text-success' : (($s2 === 'In Progress') ? 'bg-warning-subtle text-warning' : 'bg-light text-muted');
                                                ?>
                                                <span class="badge <?= $badge2 ?> border"><?= $s2 ?></span>
                                            </td>
                                            <td class="pe-4">
                                                <a href="view_student.php?id=<?= $alloc['student_id'] ?>" class="btn btn-sm btn-white border shadow-sm">Profile</a>
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

<script>
    document.getElementById('studentSearch').addEventListener('input', function() {
        const filter = this.value.toLowerCase();
        const rows = document.querySelectorAll('.notion-table tbody tr');

        rows.forEach(row => {
            if (row.cells.length === 1 && row.cells[0].colSpan > 1) return;
            const text = row.cells[0].textContent.toLowerCase();
            if (text.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>