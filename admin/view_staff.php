<?php

/**
 * View Staff Details - Admin Portal - Premium Technical Redesign
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

if (!isset($_GET['id'])) {
    header("Location: manage_staff.php");
    exit;
}

$staff_id = intval($_GET['id']);
$conn = getDbConnection();

// Fetch Staff Details
$stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$staff) {
    $_SESSION['error'] = "Staff member not found.";
    header("Location: manage_staff.php");
    exit;
}

// Fetch Assigned Subjects with Lab Details
$subjStmt = $conn->prepare("
    SELECT s.*, l.lab_name, sa.assigned_at
    FROM subjects s
    JOIN staff_assignments sa ON s.id = sa.subject_id
    JOIN labs l ON sa.lab_id = l.id
    WHERE sa.staff_id = ?
    ORDER BY s.subject_name ASC
");
$subjStmt->execute([$staff_id]);
$assignments = $subjStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Activity Logs
$logStmt = $conn->prepare("SELECT * FROM activity_logs WHERE user_id = ? AND user_role = 'staff' ORDER BY created_at DESC LIMIT 50");
$logStmt->execute([$staff_id]);
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Node Overseer: ' . $staff['name'];
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <!-- Header -->
            <div class="row align-items-center mb-4 animate-fade-up">
                <div class="col">
                    <div class="d-flex align-items-center gap-3">
                        <a href="manage_staff.php" class="btn btn-white border shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="page-title mb-0 text-dark">Staff Asset Oversight</h1>
                            <p class="text-muted small mb-0 fw-bold font-monospace">STAFF_NODE: <?= sanitizeOutput($staff['staff_id']) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex gap-2">
                        <button class="btn btn-white border shadow-sm rounded-pill px-4 fw-bold hover-bg-light" onclick="location.href='manage_staff.php?search=<?= urlencode($staff['name']) ?>&action_trigger=edit'">
                            <i class="bi bi-pencil-square me-2"></i>Modify Asset
                        </button>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Profile Identity Card -->
                <div class="col-lg-4">
                    <div class="card card-notion border-0 shadow-sm h-100 d-flex flex-column overflow-hidden animate-fade-up delay-100">
                        <div class="card-notion-body p-4 text-center flex-grow-1">
                            <?php $avatar = getProfileImage($staff['gender'] ?? 'Male', $staff['id'], $staff['avatar_id'] ?? null); ?>
                            <div class="mb-4 position-relative d-inline-block">
                                <img src="../assets/images/Avatar/<?= $avatar ?>" class="rounded-circle shadow-lg" width="120" height="120" style="border: 4px solid #fff;">
                                <div class="position-absolute bottom-0 end-0 bg-indigo border border-white rounded-circle shadow-sm" style="width: 20px; height: 20px;"></div>
                            </div>

                            <h4 class="fw-bold text-dark mb-1"><?= sanitizeOutput($staff['name']) ?></h4>
                            <p class="text-secondary small fw-bold font-monospace mb-4"><?= sanitizeOutput($staff['email']) ?></p>

                            <div class="d-flex justify-content-center gap-2 mb-4">
                                <span class="badge bg-indigo-subtle text-indigo px-3 py-2 rounded-pill border fw-bold x-small">
                                    <?= strtoupper(sanitizeOutput($staff['department'])) ?>
                                </span>
                                <span class="badge bg-light text-dark px-3 py-2 rounded-pill border fw-bold x-small">
                                    <?= strtoupper(sanitizeOutput($staff['designation'] ?? 'FACULTY')) ?>
                                </span>
                            </div>

                            <hr class="text-secondary opacity-5 my-4">

                            <div class="text-start">
                                <h6 class="x-small text-muted fw-bold text-uppercase ls-1 mb-3">Service Telemetry</h6>
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light bg-opacity-50 rounded-3">
                                    <span class="x-small text-secondary fw-medium">Active Modules</span>
                                    <span class="small fw-bold text-dark"><?= count($assignments) ?> Units</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light bg-opacity-50 rounded-3">
                                    <span class="x-small text-secondary fw-medium">Registry Date</span>
                                    <span class="small fw-bold text-dark"><?= date('M Y', strtotime($staff['created_at'])) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center p-2 bg-light bg-opacity-50 rounded-3">
                                    <span class="x-small text-secondary fw-medium">Status</span>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 rounded-pill x-small fw-bold px-3">OPERATIONAL</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assignment & Logic Tabs -->
                <div class="col-lg-8">
                    <div class="card card-notion border-0 shadow-sm h-100 d-flex flex-column overflow-hidden animate-fade-up delay-200">
                        <div class="card-notion-body p-0 flex-grow-1">
                            <div class="d-flex border-bottom overflow-x-auto">
                                <ul class="nav nav-tabs border-0 px-2" role="tablist">
                                    <li class="nav-item">
                                        <button class="nav-tab-notion nav-link active px-4 py-3 fw-bold small text-uppercase border-0" data-bs-toggle="tab" data-bs-target="#assignments" type="button" role="tab" aria-controls="assignments" aria-selected="true">
                                            <i class="bi bi-grid-3x3-gap me-2"></i>Allocated Nodes
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-tab-notion nav-link px-4 py-3 fw-bold small text-uppercase border-0" data-bs-toggle="tab" data-bs-target="#audit-logs" type="button" role="tab" aria-controls="audit-logs" aria-selected="false">
                                            <i class="bi bi-shield-check me-2"></i>Audit Trail
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <div class="tab-content h-100">
                                <!-- Allocated Nodes -->
                                <div class="tab-pane fade show active h-100" id="assignments" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table notion-table table-hover align-middle mb-0">
                                            <thead class="bg-light bg-opacity-50">
                                                <tr>
                                                    <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Module Path</th>
                                                    <th class="small text-uppercase text-secondary fw-bold">Environment</th>
                                                    <th class="small text-uppercase text-secondary fw-bold">Infrastructure</th>
                                                    <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Assignment Registry</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($assignments)): ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center py-5">
                                                            <div class="text-muted opacity-50 mb-2"><i class="bi bi-journal-x fs-1"></i></div>
                                                            <div class="text-muted small fw-medium">No active module allocations identified.</div>
                                                        </td>
                                                    </tr>
                                                <?php else: ?>
                                                    <?php foreach ($assignments as $a): ?>
                                                        <tr onclick="location.href='view_subject.php?id=<?= $a['id'] ?>'" style="cursor: pointer;">
                                                            <td class="ps-4">
                                                                <div class="fw-bold text-dark"><?= sanitizeOutput($a['subject_name']) ?></div>
                                                                <div class="x-small text-muted font-monospace"><?= $a['subject_code'] ?></div>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-indigo-subtle text-indigo border rounded-pill px-3 x-small fw-bold"><?= sanitizeOutput($a['language']) ?></span>
                                                            </td>
                                                            <td>
                                                                <div class="small fw-medium text-dark"><i class="bi bi-pc-display me-1 text-primary"></i> <?= sanitizeOutput($a['lab_name']) ?></div>
                                                            </td>
                                                            <td class="pe-4 text-end">
                                                                <div class="small fw-bold text-dark font-monospace"><?= date('d.m.Y', strtotime($a['assigned_at'] ?? $a['created_at'])) ?></div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Audit Trail -->
                                <div class="tab-pane fade h-100" id="audit-logs" role="tabpanel">
                                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                        <table class="table notion-table table-hover align-middle mb-0">
                                            <thead class="bg-light bg-opacity-50">
                                                <tr>
                                                    <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Event Vector</th>
                                                    <th class="small text-uppercase text-secondary fw-bold">Transaction Details</th>
                                                    <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Log Timestamp</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($logs)): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center py-5 text-muted small">No operational transactions archived.</td>
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