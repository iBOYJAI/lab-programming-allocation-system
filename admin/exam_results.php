<?php

/**
 * Exam Results & Reports - Enterprise Redesign
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

// Filter params
$filter_subject = $_GET['subject_id'] ?? '';
$filter_date = $_GET['date'] ?? '';

// Build query
$query = "
    SELECT sa.*, s.name as student_name, s.roll_number, 
           sub.subject_name, sub.subject_code,
           q1.question_title as q1_title, q2.question_title as q2_title,
           ss.status as submission_status, ss.submitted_at
    FROM student_allocations sa
    JOIN students s ON sa.student_id = s.id
    JOIN exam_sessions es ON sa.session_id = es.id
    JOIN subjects sub ON es.subject_id = sub.id
    JOIN questions q1 ON sa.question_1_id = q1.id
    JOIN questions q2 ON sa.question_2_id = q2.id
    LEFT JOIN (
        SELECT allocation_id, MAX(submitted_at) as submitted_at, 'Submitted' as status
        FROM submissions 
        GROUP BY allocation_id
    ) ss ON sa.id = ss.allocation_id
    WHERE 1=1
";

$params = [];
if (!empty($filter_subject)) {
    $query .= " AND es.subject_id = ?";
    $params[] = $filter_subject;
}
if (!empty($filter_date)) {
    $query .= " AND DATE(es.started_at) = ?";
    $params[] = $filter_date;
}

$query .= " ORDER BY sa.allocated_at DESC LIMIT 100";
$results = dbQuery($query, $params);

// Fetch subjects for filter
$subjects = dbQuery("SELECT * FROM subjects ORDER BY subject_name");

$page_title = 'Exam Results';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <div class="page-header d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="page-title mb-1 text-dark">Assessment Analytics</h1>
                    <p class="text-muted small mb-0">Detailed results and allocation reports.</p>
                </div>
                <button class="btn btn-white border shadow-sm fw-bold" onclick="window.print()">
                    <i class="bi bi-printer-fill me-1"></i> Export PDF
                </button>
            </div>

            <!-- Enhanced Filters -->
            <div class="card card-notion mb-4">
                <div class="card-notion-body p-4">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-lg-4 col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Subject Catalog</label>
                            <select name="subject_id" class="form-select border shadow-sm">
                                <option value="">All Academic Subjects</option>
                                <?php foreach ($subjects as $subj): ?>
                                    <option value="<?= $subj['id'] ?>" <?= $filter_subject == $subj['id'] ? 'selected' : '' ?>>
                                        <?= sanitizeOutput($subj['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-4">
                            <label class="form-label small fw-bold text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Allocation Date</label>
                            <input type="date" name="date" class="form-control border shadow-sm" value="<?= $filter_date ?>">
                        </div>
                        <div class="col-lg-5 col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1 fw-bold p-2 px-4 shadow-sm">
                                <i class="bi bi-filter me-2"></i>Apply Filters
                            </button>
                            <a href="exam_results.php" class="btn btn-light border p-2 px-4 shadow-sm">
                                <i class="bi bi-arrow-counterclockwise"></i>
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
                                    <th>Candidate</th>
                                    <th>Subject & Code</th>
                                    <th>Question Breakdown</th>
                                    <th class="pe-4">Final Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($results)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="opacity-25 mb-3">
                                                <i class="bi bi-clipboard-data display-4 text-muted"></i>
                                            </div>
                                            <h5 class="text-dark fw-bold">No assessment records found.</h5>
                                            <p class="small text-muted">Try adjusting your filters or date range.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($results as $row): ?>
                                        <tr>
                                            <td class="ps-4" style="width: 180px;">
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-dark small"><?= formatDate($row['allocated_at'], 'd M Y') ?></span>
                                                    <span class="text-muted small" style="font-size: 0.75rem;"><i class="bi bi-clock me-1"></i><?= formatDate($row['allocated_at'], 'H:i') ?></span>
                                                </div>
                                            </td>
                                            <td style="width: 250px;">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-indigo bg-opacity-10 text-indigo rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 text-shadow-none" style="width: 36px; height: 36px; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                                                        <?= strtoupper(substr($row['student_name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark small"><?= sanitizeOutput($row['student_name']) ?></div>
                                                        <span class="badge bg-light text-secondary border x-small mt-1"><?= sanitizeOutput($row['roll_number']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="width: 200px;">
                                                <div class="fw-bold text-dark small mb-1"><?= sanitizeOutput($row['subject_name']) ?></div>
                                                <span class="badge bg-white text-dark border p-1 rounded font-monospace fw-bold shadow-sm" style="font-size: 0.7rem;"><?= sanitizeOutput($row['subject_code']) ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-2 border-start ps-3" style="border-color: #f1f5f9 !important;">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-light text-muted border" style="width: 24px;">Q1</span>
                                                        <span class="text-dark small text-truncate" style="max-width: 250px;"><?= truncateText($row['q1_title'], 40) ?></span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-light text-muted border" style="width: 24px;">Q2</span>
                                                        <span class="text-dark small text-truncate" style="max-width: 250px;"><?= truncateText($row['q2_title'], 40) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="pe-4" style="width: 150px;">
                                                <?php if (!empty($row['submission_status'])): ?>
                                                    <span class="badge notion-badge live d-inline-flex align-items-center gap-2 px-3 py-2">
                                                        <i class="bi bi-check-circle-fill"></i> SUBMITTED
                                                    </span>
                                                    <div class="text-muted x-small mt-1 text-center">at <?= formatDate($row['submitted_at'], 'H:i') ?></div>
                                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 rounded-2 d-inline-flex align-items-center gap-2">
                                                        <i class="bi bi-hourglass-split"></i> PENDING
                                                    </span>
                                                <?php endif; ?>
                                                <div class="mt-2 text-end">
                                                    <a href="view_result_details.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-white border shadow-sm">View</a>
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
    .x-small {
        font-size: 0.7rem;
    }

    .q-stack {
        border-left: 2px solid #e2e8f0;
        padding-left: 10px;
    }

    @media print {

        .sidebar,
        .topbar,
        .btn,
        .card:first-of-type {
            display: none !important;
        }

        .main-wrapper {
            margin-left: 0 !important;
        }

        .main-content {
            padding: 0 !important;
        }

        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>