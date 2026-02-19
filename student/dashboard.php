<?php

/**
 * Student Dashboard - Premium Redesign
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

// Get past submissions for history
$submissions = dbQuery("
    SELECT es.session_code, es.test_type, es.started_at, s.subject_name, es.id as session_id,
           COUNT(DISTINCT sub.id) as submitted_count
    FROM exam_sessions es
    INNER JOIN student_allocations sa ON es.id = sa.session_id
    INNER JOIN subjects s ON es.subject_id = s.id
    LEFT JOIN submissions sub ON sa.id = sub.allocation_id
    WHERE sa.student_id = ? AND es.status = 'Completed'
    GROUP BY es.id
    ORDER BY es.started_at DESC
", [$student_db_id]);

// Get enrolled subjects
$enrolled_subjects = dbQuery("
    SELECT s.*
    FROM subjects s
    INNER JOIN student_subjects ss ON s.id = ss.subject_id
    WHERE ss.student_id = ?
    ORDER BY s.subject_name ASC
", [$student_db_id]);

// Get performance summary
$performance = dbQuery("
    SELECT AVG(marks) as avg_marks, COUNT(DISTINCT allocation_id) as exams_taken
    FROM submissions sub
    INNER JOIN student_allocations sa ON sub.allocation_id = sa.id
    WHERE sa.student_id = ?
", [$student_db_id])[0];

// Check for any currently active session for this student
$active_session = dbQuery("
    SELECT es.*, s.subject_name, l.lab_name
    FROM exam_sessions es
    INNER JOIN student_allocations sa ON es.id = sa.session_id
    INNER JOIN subjects s ON es.subject_id = s.id
    INNER JOIN labs l ON es.lab_id = l.id
    WHERE sa.student_id = ? AND es.status = 'Active'
    LIMIT 1
", [$student_db_id]);

$page_title = 'Student Dashboard';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_student.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_student.php'; ?>

        <div class="main-content">
            <!-- Student Hero Section -->
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
                <div>
                    <h1 class="fw-bold mb-1 text-dark fs-3">Welcome back, <?= sanitizeOutput(explode(' ', $student['name'])[0]) ?>!</h1>
                    <p class="text-secondary mb-0">Track your progress and complete your programming lab assignments.</p>
                </div>

                <?php if (!empty($active_session)): ?>
                    <div class="d-flex align-items-center gap-3">
                        <div class="active-badge animate-pulse">
                            <i class="bi bi-broadcast me-2"></i> Live Session
                        </div>
                        <a href="exam.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                            Join Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php echo displayAlert(); ?>

            <div class="row g-4">
                <!-- Stats Overview -->
                <div class="col-12">
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="card-notion stats-card animate-fade-up">
                                <div class="card-notion-body p-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="stats-icon bg-blue-subtle text-blue">
                                            <i class="bi bi-journal-check"></i>
                                        </div>
                                        <div>
                                            <div class="text-secondary small fw-bold text-uppercase">Completed</div>
                                            <div class="h3 fw-bold mb-0 text-dark"><?= count($submissions) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card-notion stats-card animate-fade-up delay-100">
                                <div class="card-notion-body p-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="stats-icon bg-green-subtle text-green">
                                            <i class="bi bi-book"></i>
                                        </div>
                                        <div>
                                            <div class="text-secondary small fw-bold text-uppercase">Enrollments</div>
                                            <div class="h3 fw-bold mb-0 text-dark"><?= count($enrolled_subjects) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card-notion stats-card animate-fade-up delay-200">
                                <div class="card-notion-body p-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="stats-icon bg-purple-subtle text-purple">
                                            <i class="bi bi-graph-up-arrow"></i>
                                        </div>
                                        <div>
                                            <div class="text-secondary small fw-bold text-uppercase">Avg Score</div>
                                            <div class="h3 fw-bold mb-0 text-dark"><?= number_format($performance['avg_marks'] ?? 0, 1) ?>%</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card-notion stats-card animate-fade-up delay-300">
                                <div class="card-notion-body p-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="stats-icon bg-orange-subtle text-orange">
                                            <i class="bi bi-award"></i>
                                        </div>
                                        <div>
                                            <div class="text-secondary small fw-bold text-uppercase">Semester</div>
                                            <div class="h3 fw-bold mb-0 text-dark"><?= sanitizeOutput($student['semester']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrolled Subjects -->
                <div class="col-12">
                    <div class="card-notion animate-fade-up delay-100 border-0 shadow-sm overflow-hidden mb-4">
                        <div class="card-header bg-white border-bottom p-4">
                            <h5 class="fw-bold mb-0 text-dark">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-collection.svg" width="22" class="me-2">
                                My Active Subjects
                            </h5>
                        </div>
                        <div class="card-notion-body p-4">
                            <div class="row g-4">
                                <?php if (empty($enrolled_subjects)): ?>
                                    <div class="col-12 text-center py-5">
                                        <div class="opacity-25 mb-3">
                                            <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-folder.svg" width="64">
                                        </div>
                                        <h6 class="text-dark fw-bold mb-1">No Active Enrollments</h6>
                                        <p class="text-muted small">Contact your administrator to map subjects to your account.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($enrolled_subjects as $subj): ?>
                                        <div class="col-md-4">
                                            <div class="subject-card-premium border rounded-4 p-4 shadow-sm h-100 transition-all">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div class="subject-icon-box bg-blue-subtle text-blue">
                                                        <i class="bi bi-code-square"></i>
                                                    </div>
                                                    <span class="badge bg-light text-secondary border px-2"><?= sanitizeOutput($subj['subject_code']) ?></span>
                                                </div>
                                                <h6 class="fw-bold text-dark mb-1"><?= sanitizeOutput($subj['subject_name']) ?></h6>
                                                <p class="text-muted small mb-3"><?= sanitizeOutput($subj['language']) ?> Programming</p>
                                                <div class="d-grid mt-auto">
                                                    <a href="subject_details.php?id=<?= $subj['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill fw-medium">View Detailed Progress</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Exam History Migration -->
                <div class="col-lg-8">
                    <div class="card card-notion overflow-hidden border-0 shadow-sm animate-fade-up">
                        <div class="card-header bg-white border-bottom p-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0 text-dark">
                                    <i class="bi bi-clock-history text-primary me-2"></i> Performance Archive Registry
                                </h6>
                                <a href="history.php" class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold transition-all">
                                    Open Full Records <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                        <div class="card-notion-body p-4 text-center">
                            <div class="opacity-10 mb-3">
                                <i class="bi bi-journal-text" style="font-size: 3rem;"></i>
                            </div>
                            <h6 class="fw-bold text-dark">Centralized Evaluation Vault</h6>
                            <p class="text-muted small mb-0 px-md-5">Your complete history of exam submissions, staff feedback, and score analysis has been consolidated into a dedicated high-fidelity registry for better accessibility.</p>
                        </div>
                    </div>
                </div>

                <!-- Profile Snapshot -->
                <div class="col-lg-4">
                    <div class="card-notion animate-fade-up delay-200 mb-4 border-0 shadow-sm overflow-hidden">
                        <div class="card-header bg-white border-bottom p-4">
                            <h5 class="fw-bold mb-0 text-dark">Profile Snapshot</h5>
                        </div>
                        <div class="card-notion-body p-4 text-center">
                            <div class="mb-3">
                                <div class="avatar-circle-lg mx-auto shadow-sm">
                                    <img src="../assets/images/Avatar/<?= getProfileImage($student['gender'] ?? 'Male', $student['id'], $student['avatar_id'] ?? null) ?>" width="80" class="rounded-circle">
                                </div>
                            </div>
                            <h5 class="text-dark fw-bold mb-1"><?= sanitizeOutput($student['name']) ?></h5>
                            <p class="text-muted small mb-3"><?= sanitizeOutput($student['roll_number'] ?? 'N/A') ?></p>

                            <hr class="my-3 opacity-10">

                            <div class="row text-start g-2">
                                <div class="col-6">
                                    <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.65rem;">Department</small>
                                    <div class="text-dark small fw-bold text-truncate"><?= sanitizeOutput($student['department'] ?? 'N/A') ?></div>
                                </div>
                                <div class="col-6">
                                    <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.65rem;">Account Status</small>
                                    <div class="text-green small fw-bold">Active</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wide Programming Tip Banner -->
            <div class="row g-4 animate-fade-up delay-400 mt-4">
                <div class="col-12">
                    <div class="card-notion bg-blue-subtle border-0 shadow-sm overflow-hidden mb-4">
                        <div class="card-notion-body p-4 d-flex align-items-center gap-4">
                            <div class="stats-icon bg-white text-blue shadow-sm fs-4">
                                <i class="bi bi-lightbulb-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-blue">Programming Tip of the Day</h6>
                                <?php
                                $tips = [
                                    "Clean code is not written, it's rewritten.",
                                    "First, solve the problem. Then, write the code.",
                                    "Programs must be written for people to read.",
                                    "The best error message is the one that never appears.",
                                    "Simple is better than complex.",
                                    "Code is a liability, not an asset.",
                                    "Don't comment bad code—rewrite it."
                                ];
                                $daily_tip = $tips[date('w') % count($tips)];
                                ?>
                                <p class="mb-0 text-blue opacity-75 fw-medium">"<?= $daily_tip ?>"</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- History Details Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-bottom p-4 bg-light">
                <div>
                    <h5 class="fw-bold mb-0">Exam Submission Review</h5>
                    <p class="text-secondary small mb-0" id="histModalSubTitle"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="histModalBody">
                <div class="p-5 text-center" id="histLoader">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <!-- Content will be injected here -->
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-circle-lg {
        width: 80px;
        height: 80px;
        background: #f8f9fa;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 2px solid #fff;
    }

    .stats-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .active-badge {
        background: rgba(var(--bs-primary-rgb), 0.1);
        color: var(--bs-primary);
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .subject-card-premium {
        background: #fff;
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    }

    .subject-card-premium:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05) !important;
        border-color: var(--bs-primary) !important;
    }

    .subject-icon-box {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
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
        const loader = document.getElementById('histLoader');

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
                                    <h6 class="fw-bold mb-3 d-flex justify-content-between">
                                        Q1: ${data.q1.title}
                                        <span class="badge bg-light text-dark border">${data.q1.marks}/50</span>
                                    </h6>
                                    <div class="code-review-box mb-3">${escapeHtml(data.q1.code)}</div>
                                    <div class="small p-2 bg-light rounded border-start border-3 border-warning">
                                        <strong>Staff Remarks:</strong> ${data.q1.remarks}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3 d-flex justify-content-between">
                                        Q2: ${data.q2.title}
                                        <span class="badge bg-light text-dark border">${data.q2.marks}/50</span>
                                    </h6>
                                    <div class="code-review-box mb-3">${escapeHtml(data.q2.code)}</div>
                                    <div class="small p-2 bg-light rounded border-start border-3 border-warning">
                                        <strong>Staff Remarks:</strong> ${data.q2.remarks}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    body.innerHTML = html;
                } else {
                    body.innerHTML = `<div class="p-5 text-center text-danger">${data.message}</div>`;
                }
            })
            .catch(err => {
                body.innerHTML = `<div class="p-5 text-center text-danger">Failed to load details.</div>`;
            });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
</script>

<?php include '../includes/footer.php'; ?>