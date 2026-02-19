<?php

/**
 * View Student Profile - Staff Portal - Premium Technical Redesign
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['staff'], '../auth.php');

if (!isset($_GET['id'])) {
    header("Location: manage_students.php");
    exit;
}

$student_id = intval($_GET['id']);
$conn = getDbConnection();

// Fetch Student Data
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    $_SESSION['error'] = "Student not found";
    header("Location: manage_students.php");
    exit;
}

// Fetch Allocation History with Marks and Dynamic Score lookup
$histStmt = $conn->prepare("
    SELECT sa.*, es.session_code, s.subject_name, COALESCE(r.total_marks, 100) as total_marks,
           (SELECT SUM(marks) FROM submissions s1 
            WHERE s1.allocation_id = sa.id 
            AND s1.id IN (SELECT MAX(id) FROM submissions WHERE allocation_id = sa.id GROUP BY question_id)
           ) as score
    FROM student_allocations sa
    JOIN exam_sessions es ON sa.session_id = es.id
    JOIN subjects s ON es.subject_id = s.id
    LEFT JOIN results r ON (es.id = r.session_id AND sa.student_id = r.student_id)
    WHERE sa.student_id = ? 
    ORDER BY sa.id DESC
");
$histStmt->execute([$student_id]);
$history = $histStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Enrolled Subjects
$enrolledStmt = $conn->prepare("
    SELECT s.*, ss.enrolled_at 
    FROM subjects s
    JOIN student_subjects ss ON s.id = ss.subject_id
    WHERE ss.student_id = ?
    ORDER BY s.subject_name ASC
");
$enrolledStmt->execute([$student_id]);
$enrolled_subjects = $enrolledStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Activity Logs
$logStmt = $conn->prepare("SELECT * FROM activity_logs WHERE user_id = ? AND user_role = 'student' ORDER BY created_at DESC LIMIT 50");
$logStmt->execute([$student_id]);
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Candidate Node: ' . $student['name'];
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <!-- Header -->
            <div class="row align-items-center mb-4 animate-fade-up">
                <div class="col">
                    <div class="d-flex align-items-center gap-3">
                        <a href="manage_students.php" class="btn btn-white border shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="page-title mb-0 text-dark">Candidate Node Detail</h1>
                            <p class="text-muted small mb-0 fw-bold font-monospace">ROSTER_NODE: <?= sanitizeOutput($student['roll_number']) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-white border shadow-sm rounded-pill px-4 fw-bold hover-bg-light" onclick="window.print()">
                        <i class="bi bi-printer me-2"></i>Export Ledger
                    </button>
                </div>
            </div>

            <div class="row g-4">
                <!-- Profile Identity Card -->
                <div class="col-lg-4">
                    <div class="card card-notion border-0 shadow-sm h-100 d-flex flex-column overflow-hidden animate-fade-up delay-100">
                        <div class="card-notion-body p-4 text-center flex-grow-1">
                            <?php $avatar = getProfileImage($student['gender'], $student['id'], $student['avatar_id'] ?? null); ?>
                            <div class="mb-4 position-relative d-inline-block">
                                <img src="../assets/images/Avatar/<?= $avatar ?>" class="rounded-circle shadow-lg" width="120" height="120" style="border: 4px solid #fff;">
                                <div class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle shadow-sm" style="width: 20px; height: 20px;"></div>
                            </div>

                            <h4 class="fw-bold text-dark mb-1"><?= sanitizeOutput($student['name']) ?></h4>
                            <p class="text-secondary small fw-bold font-monospace mb-4"><?= sanitizeOutput($student['email']) ?></p>

                            <div class="d-flex justify-content-center gap-2 mb-4">
                                <span class="badge bg-indigo-subtle text-indigo px-3 py-2 rounded-pill border fw-bold x-small">
                                    <?= strtoupper(sanitizeOutput($student['department'])) ?>
                                </span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border fw-bold x-small">
                                    NODE: <?= sanitizeOutput($student['roll_number']) ?>
                                </span>
                            </div>

                            <hr class="text-secondary opacity-5 my-4">

                            <div class="text-start">
                                <h6 class="x-small text-muted fw-bold text-uppercase ls-1 mb-3">Academic Telemetry</h6>
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light bg-opacity-50 rounded-3">
                                    <span class="x-small text-secondary fw-medium">Cycles Executed</span>
                                    <span class="small fw-bold text-dark"><?= count($history) ?> Units</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light bg-opacity-50 rounded-3">
                                    <span class="x-small text-secondary fw-medium">Mean Accuracy</span>
                                    <?php
                                    $scores = array_filter(array_map(function ($h) {
                                        return ($h['total_marks'] > 0) ? ($h['score'] / $h['total_marks']) * 100 : null;
                                    }, $history));
                                    $mean_score = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
                                    ?>
                                    <span class="small fw-bold text-dark"><?= number_format($mean_score, 1) ?>%</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center p-2 bg-light bg-opacity-50 rounded-3">
                                    <span class="x-small text-secondary fw-medium">First Registry</span>
                                    <span class="small fw-bold text-dark"><?= date('M Y', strtotime($student['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History & Logistics Tabs -->
                <div class="col-lg-8">
                    <div class="card card-notion border-0 shadow-sm h-100 d-flex flex-column overflow-hidden animate-fade-up delay-200">
                        <div class="card-notion-body p-0 flex-grow-1">
                            <div class="border-bottom overflow-x-auto">
                                <ul class="nav nav-tabs border-0 px-2" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-tab-notion nav-link active px-4 py-3 fw-bold small text-uppercase border-0" data-bs-toggle="tab" data-bs-target="#registry" type="button" role="tab" aria-controls="registry" aria-selected="true">
                                            <i class="bi bi-archive me-2"></i>Archive Registry
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-tab-notion nav-link px-4 py-3 fw-bold small text-uppercase border-0" data-bs-toggle="tab" data-bs-target="#subscriptions" type="button" role="tab" aria-controls="subscriptions" aria-selected="false">
                                            <i class="bi bi-collection me-2"></i>Subscription Nodes
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-tab-notion nav-link px-4 py-3 fw-bold small text-uppercase border-0" data-bs-toggle="tab" data-bs-target="#operation-logs" type="button" role="tab" aria-controls="operation-logs" aria-selected="false">
                                            <i class="bi bi-activity me-2"></i>Operation Logs
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div class="tab-content h-100">
                                <!-- Archive Registry -->
                                <div class="tab-pane fade show active h-100" id="registry" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table notion-table table-hover align-middle mb-0">
                                            <thead class="bg-light bg-opacity-50">
                                                <tr>
                                                    <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Subject Node</th>
                                                    <th class="small text-uppercase text-secondary fw-bold">Telemetry</th>
                                                    <th class="small text-uppercase text-secondary fw-bold">Performance</th>
                                                    <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Registry Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($history)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center py-5">
                                                            <div class="text-muted opacity-50 mb-2"><i class="bi bi-journal-x fs-1"></i></div>
                                                            <div class="text-muted small fw-medium">No archived cycles found in this registry.</div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($history as $h): ?>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="fw-bold text-dark"><?= sanitizeOutput($h['subject_name']) ?></div>
                                                                <div class="x-small text-muted font-monospace"><?= $h['session_code'] ?></div>
                                                            </td>
                                                            <td>
                                                                <?php if ($h['score'] !== null): ?>
                                                                    <span class="fw-bold text-dark small"><?= $h['score'] ?> / <?= $h['total_marks'] ?></span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-light text-secondary border x-small fw-bold">IDLE</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if ($h['score'] !== null):
                                                                    $percent = ($h['total_marks'] > 0) ? ($h['score'] / $h['total_marks']) * 100 : 0;
                                                                    $colorClass = $percent >= 75 ? 'bg-indigo' : ($percent >= 50 ? 'bg-warning' : 'bg-danger');
                                                                ?>
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <div class="progress" style="width: 50px; height: 4px; background-color: #f0f0f0;">
                                                                            <div class="progress-bar <?= $colorClass ?>" style="width: <?= $percent ?>%"></div>
                                                                        </div>
                                                                        <span class="x-small fw-bold text-dark"><?= number_format($percent, 1) ?>%</span>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <span class="text-muted x-small">--</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="pe-4 text-end">
                                                                <div class="small fw-bold text-dark font-monospace"><?= date('d.m.y', strtotime($h['allocated_at'] ?? $h['created_at'])) ?></div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Subscription Nodes -->
                                <div class="tab-pane fade h-100" id="subscriptions" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table notion-table table-hover align-middle mb-0">
                                            <thead class="bg-light bg-opacity-50">
                                                <tr>
                                                    <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Resource Identifier</th>
                                                    <th class="small text-uppercase text-secondary fw-bold">Environment</th>
                                                    <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Registry Duration</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($enrolled_subjects)): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center py-5">
                                                            <div class="text-muted opacity-50 mb-2"><i class="bi bi-layers-half fs-1"></i></div>
                                                            <div class="text-muted small fw-medium">No active resource subscriptions detected.</div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($enrolled_subjects as $s): ?>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="fw-bold text-dark"><?= sanitizeOutput($s['subject_name']) ?></div>
                                                                <span class="badge bg-light text-dark border font-monospace x-small"><?= sanitizeOutput($s['subject_code']) ?></span>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-indigo-subtle text-indigo border rounded-pill px-3 x-small fw-bold"><?= sanitizeOutput($s['language']) ?></span>
                                                            </td>
                                                            <td class="pe-4 text-end">
                                                                <div class="small fw-bold text-dark font-monospace"><?= date('M j, Y', strtotime($s['enrolled_at'])) ?></div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Operation Logs -->
                                <div class="tab-pane fade h-100" id="operation-logs" role="tabpanel">
                                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                        <table class="table notion-table table-hover align-middle mb-0">
                                            <thead class="bg-light bg-opacity-50">
                                                <tr>
                                                    <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Event Type</th>
                                                    <th class="small text-uppercase text-secondary fw-bold">Vector Details</th>
                                                    <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Event Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($logs)): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center py-5 text-muted small">No operational logs archived.</td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($logs as $l): ?>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <span class="badge bg-light text-dark border font-monospace x-small"><?= strtoupper(sanitizeOutput($l['activity_type'])) ?></span>
                                                            </td>
                                                            <td class="small text-secondary fw-medium"><?= sanitizeOutput($l['description']) ?></td>
                                                            <td class="text-muted x-small pe-4 text-end font-monospace"><?= date('M j, H:i', strtotime($l['created_at'])) ?></td>
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
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>