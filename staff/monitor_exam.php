<?php

/**
 * Monitor Exam - Real-time Monitoring Panel
 * Shows all students, their allocated questions, and submission status
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['staff'], 'login.php');

$session_id = intval($_GET['session_id'] ?? 0);

if (!$session_id) {
    // Show active sessions selection
    $active_sessions = dbQuery("
        SELECT es.*, s.subject_name, l.lab_name,
               (SELECT COUNT(*) FROM student_allocations WHERE session_id = es.id) as student_count
        FROM exam_sessions es
        INNER JOIN subjects s ON es.subject_id = s.id
        INNER JOIN labs l ON es.lab_id = l.id
        WHERE es.staff_id = ? AND es.status = 'Active'
        ORDER BY es.started_at DESC
    ", [$_SESSION['user_id']]);

    $page_title = 'Monitor Exam';
    include '../includes/header.php';
?>
    <div class="app-container">
        <?php include '../includes/sidebar_staff.php'; ?>
        <div class="main-wrapper">
            <?php include '../includes/topbar_staff.php'; ?>
            <div class="main-content">
                <div class="d-flex align-items-center gap-3 mb-4 animate-fade-up">
                    <div class="bg-warning-subtle p-2 rounded-3 text-warning">
                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-laptop-fill.svg" width="24" class="notion-icon">
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0">Monitor Exam</h4>
                        <p class="text-secondary small mb-0">Select an active session to monitor.</p>
                    </div>
                </div>

                <?php if (empty($active_sessions)): ?>
                    <div class="card-notion animate-fade-up">
                        <div class="card-notion-body text-center py-5">
                            <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-exclamation-triangle.svg" width="64" class="opacity-25 mb-3">
                            <h5 class="fw-bold text-dark">No Active Exams</h5>
                            <p class="text-muted">There are no exams currently running under your supervision.</p>
                            <a href="start_exam.php" class="btn btn-primary rounded-pill px-4 mt-2">Start an Exam</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-4 animate-fade-up">
                        <?php foreach ($active_sessions as $s): ?>
                            <div class="col-md-6 col-lg-4">
                                <a href="?session_id=<?= $s['id'] ?>" class="card-notion h-100 text-decoration-none hover-shadow transition-all">
                                    <div class="card-notion-body p-4">
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="badge bg-warning-subtle text-warning border-0">LIVE</span>
                                            <code class="text-primary small"><?= $s['session_code'] ?></code>
                                        </div>
                                        <h5 class="fw-bold text-dark mb-1"><?= sanitizeOutput($s['subject_name']) ?></h5>
                                        <p class="text-secondary small mb-3"><?= sanitizeOutput($s['lab_name']) ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small"><?= $s['student_count'] ?> Students</span>
                                            <span class="btn btn-sm btn-light border rounded-pill">Monitor <i class="bi bi-arrow-right ms-1"></i></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
<?php exit;
}

// Get session details
$session = dbQuery("
    SELECT es.*, s.subject_name, s.language, l.lab_name
    FROM exam_sessions es
    INNER JOIN subjects s ON es.subject_id = s.id
    INNER JOIN labs l ON es.lab_id = l.id
    WHERE es.id = ? AND es.staff_id = ?
", [$session_id, $_SESSION['user_id']]);

if (empty($session)) {
    redirect('dashboard.php');
}

$session = $session[0];

// Get all allocations with student and question details
$allocations = dbQuery("
    SELECT sa.*, 
           st.roll_number, st.name as student_name,
           q1.question_title as q1_title,
           q2.question_title as q2_title,
           (SELECT COUNT(*) FROM submissions WHERE allocation_id = sa.id AND question_id = sa.question_1_id) as q1_submitted,
           (SELECT COUNT(*) FROM submissions WHERE allocation_id = sa.id AND question_id = sa.question_2_id) as q2_submitted
    FROM student_allocations sa
    INNER JOIN students st ON sa.student_id = st.id
    INNER JOIN questions q1 ON sa.question_1_id = q1.id
    INNER JOIN questions q2 ON sa.question_2_id = q2.id
    WHERE sa.session_id = ?
    ORDER BY sa.system_no
", [$session_id]);

$page_title = 'Monitor Exam';
include '../includes/header.php';
?>

<?php include '../includes/navbar_staff.php'; ?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <!-- Header Section -->
            <div class="row g-3 mb-4 animate-fade-up">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="notion-badge bg-warning text-dark border-0">
                            <div class="spinner-grow spinner-grow-sm me-1" style="width: 0.5rem; height: 0.5rem;"></div> LIVE MONITOR
                        </span>
                        <span class="text-muted small fw-bold text-uppercase tracking-wide"><?= sanitizeOutput($session['session_code']) ?></span>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark"><?= sanitizeOutput($session['subject_name']) ?></h2>
                    <p class="text-secondary small mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-geo-alt"></i> <?= sanitizeOutput($session['lab_name']) ?>
                    </p>
                </div>
                <!-- Submission Key Card -->
                <div class="col-md-4">
                    <div class="notion-stat-card border-primary bg-primary bg-opacity-10 py-2">
                        <div class="d-flex align-items-center justify-content-center gap-3">
                            <div class="text-center">
                                <div class="notion-stat-label text-primary text-uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 1px;">Current Submission Key</div>
                                <div class="notion-stat-value text-primary fs-3 fw-black tracking-widest" id="submission-key">
                                    <?= rotateSessionKey($session_id) ?>
                                </div>
                                <div class="progress mt-1" style="height: 3px; background: rgba(13, 110, 253, 0.1);">
                                    <div id="key-progress" class="progress-bar progress-bar-animated" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 d-flex flex-column gap-2 justify-content-center">
                    <?php if ($session['status'] === 'Active'): ?>
                        <button onclick="confirmAction('Stop this exam session? This will prevent further submissions.', function() { window.location='stop_exam.php?session_id=<?= $session_id ?>'; })"
                            class="btn btn-danger btn-sm d-flex align-items-center justify-content-center gap-2 shadow-sm">
                            <i class="bi bi-stop-circle-fill"></i> Stop Exam
                        </button>
                    <?php endif; ?>
                    <button onclick="location.reload()" class="btn btn-white btn-sm border shadow-sm d-flex align-items-center justify-content-center gap-2 text-dark hover-bg-light">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Status Summary Cards -->
            <div class="row g-3 mb-4 animate-fade-up delay-100">
                <?php
                $total = count($allocations);
                $submitted = 0;
                $in_progress = 0;
                $not_started = 0;

                foreach ($allocations as $alloc) {
                    if ($alloc['status_q1'] === 'Submitted' && $alloc['status_q2'] === 'Submitted') {
                        $submitted++;
                    } elseif ($alloc['status_q1'] !== 'Not Started' || $alloc['status_q2'] !== 'Not Started') {
                        $in_progress++;
                    } else {
                        $not_started++;
                    }
                }
                ?>
                <div class="col-md-3">
                    <div class="notion-stat-card h-100 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="notion-stat-icon-wrapper blue">
                                <i class="bi bi-people d-flex align-items-center justify-content-center w-100 h-100 fs-5"></i>
                            </div>
                            <div>
                                <div class="notion-stat-label">Total Students</div>
                                <div class="notion-stat-value"><?= $total ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="notion-stat-card h-100 py-3 border-success-subtle bg-success-subtle bg-opacity-10">
                        <div class="d-flex align-items-center gap-3">
                            <div class="notion-stat-icon-wrapper green">
                                <i class="bi bi-check-lg d-flex align-items-center justify-content-center w-100 h-100 fs-5"></i>
                            </div>
                            <div>
                                <div class="notion-stat-label text-success">Completed</div>
                                <div class="notion-stat-value text-success"><?= $submitted ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="notion-stat-card h-100 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="notion-stat-icon-wrapper yellow">
                                <i class="bi bi-hourglass-split d-flex align-items-center justify-content-center w-100 h-100 fs-5"></i>
                            </div>
                            <div>
                                <div class="notion-stat-label">In Progress</div>
                                <div class="notion-stat-value"><?= $in_progress ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <?php
                    $duration_seconds = $session['duration_minutes'] * 60;
                    $startTime = strtotime($session['started_at']);
                    $endTime = $startTime + $duration_seconds;
                    $remaining = $endTime - time();
                    if ($remaining < 0) $remaining = 0;
                    ?>
                    <div class="notion-stat-card h-100 py-3 <?= $remaining < 300 ? 'border-danger-subtle bg-danger-subtle bg-opacity-10' : '' ?>">
                        <div class="d-flex align-items-center gap-3">
                            <div class="notion-stat-icon-wrapper <?= $remaining < 300 ? 'red' : 'gray' ?>">
                                <i class="bi bi-clock d-flex align-items-center justify-content-center w-100 h-100 fs-5"></i>
                            </div>
                            <div>
                                <div class="notion-stat-label <?= $remaining < 300 ? 'text-danger' : '' ?>">Time Remaining</div>
                                <div class="notion-stat-value <?= $remaining < 300 ? 'text-danger' : '' ?>" id="session-timer">--:--</div>
                                <input type="hidden" id="remaining_seconds" value="<?= $remaining ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Monitoring Table -->
            <div class="card-notion animate-fade-up delay-200">
                <div class="card-notion-header">
                    <h5 class="card-notion-title">
                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-list.svg" width="18" class="opacity-50">
                        Real-time Status Board
                    </h5>
                    <div class="small text-muted">Auto-refreshes every 5s</div>
                </div>
                <div class="card-notion-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="monitorTable">
                            <thead class="bg-light border-bottom">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary text-uppercase small fw-bold" style="width: 80px;">System</th>
                                    <th class="py-3 text-secondary text-uppercase small fw-bold">Student</th>
                                    <th class="py-3 text-secondary text-uppercase small fw-bold">Task 1 (Q1)</th>
                                    <th class="py-3 text-secondary text-uppercase small fw-bold">Task 2 (Q2)</th>
                                    <th class="pe-4 py-3 text-end text-secondary text-uppercase small fw-bold">Overall</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allocations as $alloc): ?>
                                    <?php
                                    $overall_status = 'not-started';
                                    if ($alloc['status_q1'] === 'Submitted' && $alloc['status_q2'] === 'Submitted') {
                                        $overall_status = 'submitted';
                                    } elseif ($alloc['status_q1'] !== 'Not Started' || $alloc['status_q2'] !== 'Not Started') {
                                        $overall_status = 'in-progress';
                                    }
                                    ?>
                                    <tr class="border-bottom-0 hover-bg-light transition-all">
                                        <td class="ps-4">
                                            <div class="bg-light border rounded px-2 py-1 text-center fw-bold text-dark small" style="width: 40px;">
                                                <?= $alloc['system_no'] ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-dark"><?= sanitizeOutput($alloc['roll_number']) ?></span>
                                                <small class="text-muted"><?= sanitizeOutput($alloc['student_name']) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($alloc['q1_submitted']): ?>
                                                    <i class="bi bi-check-circle-fill text-success"></i>
                                                    <span class="text-decoration-line-through text-muted small">
                                                        <?= truncateText(sanitizeOutput($alloc['q1_title']), 30) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <i class="bi bi-circle text-secondary"></i>
                                                    <span class="text-dark small">
                                                        <?= truncateText(sanitizeOutput($alloc['q1_title']), 30) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($alloc['q2_submitted']): ?>
                                                    <i class="bi bi-check-circle-fill text-success"></i>
                                                    <span class="text-decoration-line-through text-muted small">
                                                        <?= truncateText(sanitizeOutput($alloc['q2_title']), 30) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <i class="bi bi-circle text-secondary"></i>
                                                    <span class="text-dark small">
                                                        <?= truncateText(sanitizeOutput($alloc['q2_title']), 30) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <?php if ($overall_status === 'submitted'): ?>
                                                <span class="notion-badge bg-success-subtle text-success border-success-subtle">Completed</span>
                                            <?php elseif ($overall_status === 'in-progress'): ?>
                                                <span class="notion-badge bg-warning-subtle text-warning border-warning-subtle"> In Progress </span>
                                            <?php else: ?>
                                                <span class="notion-badge bg-secondary-subtle text-secondary border-secondary-subtle">Not Started</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-refresh data every 5 seconds
    setInterval(function() {
        // We could use AJAX here to avoid page refresh, but for now full refresh is simpler
        // location.reload(); 
    }, 5000);

    // Key Rotation logic
    let keyTimer = 60;

    function updateSubmissionKey() {
        $.ajax({
            url: 'api/get_submission_key.php',
            data: {
                session_id: <?= $session_id ?>
            },
            success: function(res) {
                if (res.success) {
                    $('#submission-key').text(res.key);
                    keyTimer = res.next_rotation;
                }
            }
        });
    }

    // Update key timer progress bar
    setInterval(function() {
        keyTimer--;
        let percentage = (keyTimer / 60) * 100;
        $('#key-progress').css('width', percentage + '%');

        if (keyTimer <= 0) {
            updateSubmissionKey();
            keyTimer = 60;
        }
    }, 1000);

    // Timer logic for monitor page
    const remainingInput = document.getElementById('remaining_seconds');
    if (remainingInput) {
        let timeLeft = parseInt(remainingInput.value);
        const display = document.getElementById('session-timer');

        setInterval(function() {
            if (timeLeft <= 0) {
                display.textContent = "00:00";
                return;
            }
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            display.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            timeLeft--;
        }, 1000);
    }
</script>

<?php include '../includes/footer.php'; ?>