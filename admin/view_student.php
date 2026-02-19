<?php

/**
 * View Student Profile - Premium Redesign
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

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

// Fetch Allocation History
$histStmt = $conn->prepare("
    SELECT sa.*, es.session_code, s.subject_name,
           (SELECT SUM(marks) FROM submissions s1 
            WHERE s1.allocation_id = sa.id 
            AND s1.id IN (SELECT MAX(id) FROM submissions WHERE allocation_id = sa.id GROUP BY question_id)
           ) as total_score
    FROM student_allocations sa
    JOIN exam_sessions es ON sa.session_id = es.id
    JOIN subjects s ON es.subject_id = s.id
    WHERE sa.student_id = ? 
    ORDER BY sa.id DESC
");
$histStmt->execute([$student_id]);
$history = $histStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Activity Logs
$logStmt = $conn->prepare("SELECT * FROM activity_logs WHERE user_id = ? AND user_role = 'student' ORDER BY created_at DESC LIMIT 50");
$logStmt->execute([$student_id]);
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Student Profile';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <!-- Header -->
            <div class="row align-items-center mb-4">
                <div class="col">
                    <div class="d-flex align-items-center gap-3">
                        <a href="manage_students.php" class="btn btn-white border shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="page-title mb-0 text-dark"><?= sanitizeOutput($student['name']) ?></h1>
                            <p class="text-muted small mb-0 fw-bold font-monospace">ROSTER_NODE: <?= sanitizeOutput($student['roll_number']) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex gap-2">
                        <button class="btn btn-warning text-white rounded-pill px-4 shadow-sm fw-bold transition-all" onclick="location.href='manage_students.php?search=<?= urlencode($student['roll_number']) ?>&action_trigger=edit'">
                            <i class="bi bi-pencil-square me-2"></i>Modify Records
                        </button>
                        <button class="btn btn-white border shadow-sm rounded-pill px-4 fw-bold hover-bg-light" onclick="window.print()">
                            <i class="bi bi-printer me-2"></i>Print Ledger
                        </button>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Profile Card -->
                <div class="col-lg-4">
                    <div class="card card-notion border-0 shadow-sm h-100 d-flex flex-column overflow-hidden">
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
                                    <i class="bi bi-building me-1"></i> <?= sanitizeOutput($student['department']) ?>
                                </span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border fw-bold x-small">
                                    <i class="bi bi-hash me-1"></i> <?= sanitizeOutput($student['roll_number']) ?>
                                </span>
                            </div>

                            <hr class="text-muted opacity-10 my-4">

                            <div class="text-start">
                                <h6 class="text-uppercase text-secondary fw-900 mb-3" style="font-size: 0.65rem; letter-spacing: 0.1em;">Academic Operational Metrics</h6>
                                <div class="p-3 bg-light bg-opacity-50 rounded-3 border mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted small fw-medium">Engagement Index</span>
                                        <span class="badge bg-dark text-white font-monospace"><?= count($history) ?> Events</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small fw-medium">Enrollment Date</span>
                                        <span class="small fw-bold text-dark"><?= date('M j, Y', strtotime($student['created_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="small text-muted fw-bold text-uppercase d-block mb-2" style="font-size: 0.65rem; letter-spacing: 0.1em;">Security Status</label>
                                    <div class="d-flex align-items-center gap-2 text-success">
                                        <i class="bi bi-shield-check"></i>
                                        <span class="small fw-bold">NODE_STRICT_VERIFIED</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comprehensive Data Tabs -->
                <div class="col-lg-8">
                    <div class="card card-notion border-0 shadow-sm h-100 d-flex flex-column">
                        <div class="card-header bg-white border-bottom p-0">
                            <ul class="nav nav-tabs border-bottom-0 px-4 pt-3 gap-1" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active py-3 px-4 fw-bold text-uppercase border-bottom-3 border-primary" data-bs-toggle="tab" href="#exams" role="tab" style="font-size: 0.75rem; letter-spacing: 0.05em;">Performance logs</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-3 px-4 fw-bold text-uppercase" data-bs-toggle="tab" href="#enrolled" role="tab" style="font-size: 0.75rem; letter-spacing: 0.05em;">Resource Subscriptions</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-3 px-4 fw-bold text-uppercase" data-bs-toggle="tab" href="#activity" role="tab" style="font-size: 0.75rem; letter-spacing: 0.05em;">Operation Logs</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-notion-body p-0 flex-grow-1">
                            <div class="tab-content h-100">
                                <!-- Performance History -->
                                <div class="tab-pane fade show active h-100" id="exams" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table notion-table table-hover align-middle mb-0">
                                            <thead class="bg-light bg-opacity-50">
                                                <tr>
                                                    <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Domain Node</th>
                                                    <th class="small text-uppercase text-secondary fw-bold">Session ID</th>
                                                    <th class="small text-uppercase text-secondary fw-bold text-center">Efficiency</th>
                                                    <th class="small text-uppercase text-secondary fw-bold text-center">Timestamp</th>
                                                    <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($history)): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center py-5">
                                                            <div class="text-muted opacity-50 mb-2"><i class="bi bi-clock-history fs-1"></i></div>
                                                            <div class="text-muted small fw-medium">No performance data synchronization identified.</div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($history as $h): ?>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="fw-bold text-dark lh-sm"><?= sanitizeOutput($h['subject_name']) ?></div>
                                                            </td>
                                                            <td><span class="badge bg-white border text-dark font-monospace x-small"><?= $h['session_code'] ?></span></td>
                                                            <td class="text-center">
                                                                <?php if (isset($h['total_score'])): ?>
                                                                    <span class="fw-900 <?= $h['total_score'] >= 80 ? 'text-success' : ($h['total_score'] >= 50 ? 'text-primary' : 'text-danger') ?>"><?= $h['total_score'] ?>%</span>
                                                                <?php else: ?>
                                                                    <span class="text-muted small">PENDING</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td class="text-center text-muted x-small font-monospace"><?= date('M j, Y', strtotime($h['allocated_at'] ?? $h['created_at'])) ?></td>
                                                            <td class="pe-4 text-end">
                                                                <a href="view_result_details.php?id=<?= $h['id'] ?>" class="btn btn-sm btn-white border rounded-pill shadow-sm hover-bg-light transition-all">Inspection Details</a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Subscription Management -->
                                <div class="tab-pane fade h-100" id="enrolled" role="tabpanel">
                                    <?php
                                    $enrolledStmt = $conn->prepare("
                                        SELECT s.* 
                                        FROM subjects s
                                        JOIN student_subjects ss ON s.id = ss.subject_id
                                        WHERE ss.student_id = ?
                                        ORDER BY s.subject_name ASC
                                    ");
                                    $enrolledStmt->execute([$student_id]);
                                    $subjects = $enrolledStmt->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    <div class="table-responsive">
                                        <table class="table notion-table table-hover align-middle mb-0">
                                            <thead class="bg-light bg-opacity-50">
                                                <tr>
                                                    <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Resource Identifier</th>
                                                    <th class="small text-uppercase text-secondary fw-bold">Protocol</th>
                                                    <th class="small text-uppercase text-secondary fw-bold">Language Environment</th>
                                                    <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Control</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($subjects)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center py-5">
                                                            <div class="text-muted opacity-50 mb-2"><i class="bi bi-collection-play fs-1"></i></div>
                                                            <div class="text-muted small fw-medium">No active resource subscriptions found.</div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($subjects as $s): ?>
                                                        <tr>
                                                            <td class="ps-4">
                                                                <div class="fw-bold text-dark"><?= sanitizeOutput($s['subject_name']) ?></div>
                                                            </td>
                                                            <td><span class="badge bg-light text-dark border font-monospace x-small"><?= sanitizeOutput($s['subject_code']) ?></span></td>
                                                            <td><span class="badge bg-indigo-subtle text-indigo border rounded-pill px-3"><?= sanitizeOutput($s['language']) ?></span></td>
                                                            <td class="pe-4 text-end">
                                                                <a href="view_subject.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-white border rounded-pill shadow-sm">Explore Node</a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Tactical Operation Logs -->
                                <div class="tab-pane fade h-100" id="activity" role="tabpanel">
                                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                        <table class="table notion-table table-hover align-middle mb-0">
                                            <thead class="bg-light bg-opacity-50">
                                                <tr>
                                                    <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Operation Type</th>
                                                    <th class="small text-uppercase text-secondary fw-bold">Contextual Details</th>
                                                    <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Registry Time</th>
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
                                                                <span class="badge bg-light text-dark border font-monospace x-small"><?= sanitizeOutput($l['activity_type']) ?></span>
                                                            </td>
                                                            <td class="small text-secondary fw-medium"><?= sanitizeOutput($l['description']) ?></td>
                                                            <td class="text-muted x-small pe-4 text-end font-monospace"><?= formatDate($l['created_at'], 'M j, H:i:s') ?></td>
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
</div>

<?php include '../includes/footer.php'; ?>