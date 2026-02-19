<?php

/**
 * Student Exam history - Premium View
 * Lab Programming Allocation System
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['student'], '../auth.php');

$student_db_id = $_SESSION['user_id'];

// Get student details
$student = dbQuery("SELECT * FROM students WHERE id = ?", [$student_db_id])[0];

// Get comprehensive history
$history = dbQuery("
    SELECT es.session_code, es.test_type, es.started_at, es.ended_at, s.subject_name, s.subject_code, es.id as session_id,
           r.total_marks, r.remarks,
           (SELECT COUNT(*) FROM submissions sub WHERE sub.allocation_id = sa.id) as sub_count
    FROM student_allocations sa
    JOIN exam_sessions es ON sa.session_id = es.id
    JOIN subjects s ON es.subject_id = s.id
    LEFT JOIN results r ON (es.id = r.session_id AND sa.student_id = r.student_id)
    WHERE sa.student_id = ? AND es.status = 'Completed'
    ORDER BY es.started_at DESC
", [$student_db_id]);

$page_title = 'Exam History Archive';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_student.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_student.php'; ?>

        <div class="main-content">
            <div class="row align-items-end mb-4">
                <div class="col">
                    <h1 class="page-title mb-1 text-dark">Performance Registry</h1>
                    <p class="text-muted small mb-0">Historical archive of your programming lab evaluations and milestones.</p>
                </div>
                <div class="col-auto">
                    <div class="badge bg-white text-dark border shadow-sm px-3 py-2 rounded-pill">
                        <i class="bi bi-award-fill text-warning me-2"></i> Total Exams: <?= count($history) ?>
                    </div>
                </div>
            </div>

            <div class="card card-notion overflow-hidden border-0 shadow-sm animate-fade-up">
                <div class="card-notion-body p-0">
                    <?php if (empty($history)): ?>
                        <div class="text-center py-5">
                            <div class="opacity-25 mb-3">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-folder.svg" width="64">
                            </div>
                            <h5 class="text-dark fw-bold mb-1">Archive Empty</h5>
                            <p class="text-muted">You haven't completed any evaluated sessions yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light bg-opacity-50">
                                    <tr>
                                        <th class="ps-4 text-uppercase small fw-bold text-secondary">Session details</th>
                                        <th class="text-uppercase small fw-bold text-secondary">Subject</th>
                                        <th class="text-uppercase small fw-bold text-secondary text-center">Questions</th>
                                        <th class="text-uppercase small fw-bold text-secondary text-center">Score / Grade</th>
                                        <th class="text-end pe-4 text-uppercase small fw-bold text-secondary">Operations</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $h): ?>
                                        <tr>
                                            <td class="ps-4 py-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="session-icon bg-light rounded text-dark d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <i class="bi bi-terminal-fill"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark mb-0"><?= sanitizeOutput($h['session_code']) ?></div>
                                                        <div class="text-muted x-small"><?= formatDate($h['started_at'], 'D, d M Y | h:i A') ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-dark"><?= sanitizeOutput($h['subject_name']) ?></div>
                                                <span class="badge bg-blue-subtle text-blue x-small px-2 mt-1"><?= sanitizeOutput($h['subject_code']) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <div class="progress" style="width: 60px; height: 6px;">
                                                        <div class="progress-bar bg-primary" style="width: <?= ($h['sub_count'] / 2) * 100 ?>%"></div>
                                                    </div>
                                                    <span class="small fw-bold text-dark"><?= $h['sub_count'] ?>/2</span>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($h['total_marks'] !== null): ?>
                                                    <div class="fw-900 fs-5 text-dark"><?= $h['total_marks'] ?></div>
                                                    <div class="text-success small fw-bold text-uppercase" style="font-size: 0.6rem;">COMPLETED</div>
                                                <?php else: ?>
                                                    <span class="text-muted small italic">Pending Review</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light transition-all"
                                                    onclick="viewHistoryDetails('<?= $h['session_id'] ?>')">
                                                    <i class="bi bi-eye me-1"></i> Data Audit
                                                </button>
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

<!-- Modal for details -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-4">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-fingerprint me-2"></i> Technical Audit Trail
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="histModalBody">
                <!-- Content via AJAX -->
            </div>
        </div>
    </div>
</div>

<template id="histLoader">
    <div class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted small">Accessing Encrypted Records...</p>
    </div>
</template>

<style>
    .session-icon {
        border: 1px solid #eee;
        transition: 0.3s;
    }

    tr:hover .session-icon {
        background: #000 !important;
        color: #fff !important;
        border-color: #000;
    }

    .progress {
        background: #f0f0f0;
        border-radius: 10px;
    }

    .code-review-box {
        background: #1e1e1e;
        color: #d4d4d4;
        padding: 1rem;
        border-radius: 10px;
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 0.9rem;
        max-height: 250px;
        overflow-y: auto;
    }
</style>

<script>
    function viewHistoryDetails(sessionId) {
        const modal = new bootstrap.Modal(document.getElementById('historyModal'));
        const body = document.getElementById('histModalBody');
        const loader = document.getElementById('histLoader').content.cloneNode(true);

        body.innerHTML = '';
        body.appendChild(loader);
        modal.show();

        fetch(`get_submission_details.php?session_id=${sessionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = `
                        <div class="p-4">
                            <div class="row g-4">
                                <div class="col-md-6 border-end">
                                    <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-file-earmark-code text-primary me-2"></i> Q1: ${data.q1.title}</span>
                                        <span class="badge bg-light text-dark border small">${data.q1.marks}/50</span>
                                    </h6>
                                    <div class="code-review-box mb-3 shadow-sm">${escapeHtml(data.q1.code)}</div>
                                    <div class="small p-3 bg-light rounded border-start border-4 border-warning">
                                        <strong class="text-dark d-block mb-1">Staff Audit Remarks:</strong>
                                        <span class="text-secondary">${data.q1.remarks || 'No detailed feedback provided.'}</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-file-earmark-code text-primary me-2"></i> Q2: ${data.q2.title}</span>
                                        <span class="badge bg-light text-dark border small">${data.q2.marks}/50</span>
                                    </h6>
                                    <div class="code-review-box mb-3 shadow-sm">${escapeHtml(data.q2.code)}</div>
                                    <div class="small p-3 bg-light rounded border-start border-4 border-warning">
                                        <strong class="text-dark d-block mb-1">Staff Audit Remarks:</strong>
                                        <span class="text-secondary">${data.q2.remarks || 'No detailed feedback provided.'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    body.innerHTML = html;
                } else {
                    body.innerHTML = `<div class="p-5 text-center text-danger">${data.message || 'Error fetching records.'}</div>`;
                }
            })
            .catch(err => {
                body.innerHTML = `<div class="p-5 text-center text-danger">Connection link failure.</div>`;
            });
    }

    function escapeHtml(text) {
        if (!text) return '<i>[NO CODE SUBMITTED]</i>';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
</script>

<?php include '../includes/footer.php'; ?>