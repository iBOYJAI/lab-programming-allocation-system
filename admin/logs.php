<?php

/**
 * Activity Logs - Enterprise Redesign
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

// Filter params
$filter_user = $_GET['user_id'] ?? '';
$filter_action = $_GET['action_type'] ?? '';

// Build query
$query = "
    SELECT al.*, 
           CASE 
               WHEN al.user_role = 'admin' THEN 'Administrator'
               WHEN al.user_role = 'staff' THEN st.name
               ELSE 'System'
           END as performed_by
    FROM activity_logs al
    LEFT JOIN staff st ON al.user_id = st.id AND al.user_role = 'staff'
    WHERE 1=1
";

$params = [];
if (!empty($filter_user)) {
    $query .= " AND al.user_id = ?";
    $params[] = $filter_user;
}
if (!empty($filter_action)) {
    $query .= " AND al.action_type = ?";
    $params[] = $filter_action;
}

$query .= " ORDER BY al.created_at DESC LIMIT 200";
$logs = dbQuery($query, $params);

// Fetch unique actions for filter
try {
    $actions = dbQuery("SELECT DISTINCT action_type FROM activity_logs ORDER BY action_type");
} catch (Exception $e) {
    $actions = [];
    error_log("Failed to fetch action types: " . $e->getMessage());
}

$page_title = 'Activity Logs';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title mb-1 text-dark">Audit Trails</h1>
                    <p class="text-muted small mb-0">Monitor system-wide administrative and staff activities.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-white border shadow-sm fw-bold" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </button>
                    <button class="btn btn-primary fw-bold shadow-sm" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Export
                    </button>
                </div>
            </div>

            <!-- Enhanced Filters -->
            <div class="card card-notion mb-4 d-print-none">
                <div class="card-notion-body p-4">
                    <form method="GET" class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Action Type</label>
                            <select name="action_type" class="form-select border shadow-sm">
                                <option value="">All Interactions</option>
                                <?php foreach ($actions as $act): ?>
                                    <?php
                                    $act_val = $act['action'] ?? $act['action_type'];
                                    if (!$act_val) continue;
                                    ?>
                                    <option value="<?= $act_val ?>" <?= $filter_action == $act_val ? 'selected' : '' ?>>
                                        <?= ucwords(str_replace('_', ' ', $act_val)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1 fw-bold shadow-sm">
                                <i class="bi bi-funnel me-2"></i>Filter Logs
                            </button>
                            <a href="logs.php" class="btn btn-light border shadow-sm">
                                <i class="bi bi-x-lg"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card card-notion overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table notion-table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Timestamp</th>
                                    <th>Performed By</th>
                                    <th>Action</th>
                                    <th class="pe-4">Detailed Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="opacity-25 mb-3">
                                                <i class="bi bi-journal-x display-4 text-muted"></i>
                                            </div>
                                            <p class="text-muted fw-bold">No audit trails found.</p>
                                            <p class="small text-muted mb-0">Try adjusting your filters.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td class="ps-4" style="width: 200px;">
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark small"><?= formatDate($log['created_at'], 'd M Y') ?></span>
                                                    <span class="text-muted small" style="font-size: 0.75rem;"><?= formatDate($log['created_at'], 'H:i:s') ?></span>
                                                </div>
                                            </td>
                                            <td style="width: 200px;">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center text-muted" style="width: 32px; height: 32px;">
                                                        <i class="bi bi-person-circle"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark small"><?= sanitizeOutput($log['performed_by']) ?></div>
                                                        <span class="badge bg-light text-secondary border x-small"><?= strtoupper($log['user_role']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="width: 180px;">
                                                <?php
                                                $action = $log['action'] ?? $log['action_type'] ?? 'Unknown';
                                                ?>
                                                <span class="badge bg-indigo-subtle text-indigo border px-2 py-1 rounded-2">
                                                    <?= strtoupper(str_replace('_', ' ', $action)) ?>
                                                </span>
                                            </td>
                                            <td class="pe-4">
                                                <div class="p-2 bg-light rounded text-muted small" style="border: 1px dashed #dee2e6;">
                                                    <?= sanitizeOutput($log['description']) ?>
                                                    <div class="mt-1 x-small fw-mono opacity-50">IP: <?= $log['ip_address'] ?? '0.0.0.0' ?></div>
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

            <div class="mt-4 text-center d-print-none">
                <span class="badge bg-light text-muted border rounded-pill px-3 py-2">
                    <i class="bi bi-info-circle me-1"></i> Showing last 200 system events
                </span>
            </div>
        </div>
    </div>
</div>

<style>
    .x-small {
        font-size: 0.7rem;
    }

    @media print {
        .app-container {
            margin: 0;
            padding: 0;
        }

        .sidebar,
        .topbar {
            display: none !important;
        }

        .main-wrapper {
            margin: 0 !important;
            width: 100%;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .badge {
            border: 1px solid #ccc !important;
            color: #000 !important;
            background: none !important;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>