<?php

/**
 * Start Exam - CRITICAL PAGE
 * This page triggers the allocation algorithm
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['staff'], 'login.php');

$staff_id = $_SESSION['user_id'];

// Get staff's assigned subjects
$subjects = dbQuery("
    SELECT s.*, l.id as lab_id, l.lab_name,
           (SELECT COUNT(*) FROM questions WHERE subject_id = s.id) as question_count
    FROM staff_assignments sa
    INNER JOIN subjects s ON sa.subject_id = s.id
    INNER JOIN labs l ON sa.lab_id = l.id
    WHERE sa.staff_id = ?
", [$staff_id]);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token";
    } else {
        $subject_id = intval($_POST['subject_id'] ?? 0);
        $lab_id = intval($_POST['lab_id'] ?? 0);
        $test_type = sanitizeInput($_POST['test_type'] ?? '');
        $duration = intval($_POST['duration'] ?? 0);

        if (!$subject_id || !$lab_id || !$test_type) {
            $error = "Please fill all required fields";
        } else {
            // Verify question count
            $question_check = dbQuery("SELECT COUNT(*) as count FROM questions WHERE subject_id = ?", [$subject_id])[0];

            if ($question_check['count'] < 15) {
                $error = "Subject must have at least 15 questions. Currently has {$question_check['count']} questions.";
            } else {
                // Redirect to process page
                redirect("process_start_exam.php?subject_id=$subject_id&lab_id=$lab_id&test_type=$test_type&duration=$duration");
            }
        }
    }
}

$page_title = 'Start Exam';
include '../includes/header.php';
?>

<?php include '../includes/navbar_staff.php'; ?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <div class="container-fluid">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="bg-success-subtle p-2 rounded-3 text-success">
                        <i class="bi bi-play-circle fs-4"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0">Start New Exam</h4>
                        <p class="text-secondary small mb-0">Configure and launch a new examination session.</p>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card-notion animate-fade-up">
                            <div class="card-notion-header bg-light border-bottom">
                                <h5 class="card-notion-title mb-0">Exam Configuration</h5>
                            </div>
                            <div class="card-notion-body p-4">
                                <?php if ($error): ?>
                                    <div class="alert alert-danger d-flex align-items-center gap-2">
                                        <i class="bi bi-exclamation-triangle-fill"></i> <?= sanitizeOutput($error) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (empty($subjects)): ?>
                                    <div class="text-center py-5">
                                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-folder-open.svg" width="64" class="opacity-25 mb-3">
                                        <h5 class="fw-bold text-dark">No Subjects Assigned</h5>
                                        <p class="text-secondary">Please contact the administrator to get subjects assigned to you.</p>
                                    </div>
                                <?php else: ?>
                                    <form method="POST" action="" id="startExamForm">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                                        <div class="mb-4">
                                            <label for="subject_id" class="form-label fw-bold text-dark small text-uppercase">
                                                Select Subject <span class="text-danger">*</span>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0"><i class="bi bi-book text-secondary"></i></span>
                                                <select class="form-select border-start-0 ps-0 bg-light" name="subject_id" id="subject_id" required style="border-color: var(--notion-border);">
                                                    <option value="">-- Choose Subject --</option>
                                                    <?php foreach ($subjects as $subject): ?>
                                                        <option value="<?= $subject['id'] ?>"
                                                            data-lab="<?= $subject['lab_id'] ?>"
                                                            data-questions="<?= $subject['question_count'] ?>">
                                                            <?= sanitizeOutput($subject['subject_name']) ?>
                                                            (<?= sanitizeOutput($subject['language']) ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-text text-muted mt-2" id="subject-hint">
                                                <i class="bi bi-info-circle me-1"></i> Subject must have at least 15 questions in the bank.
                                            </div>
                                        </div>

                                        <input type="hidden" name="lab_id" id="lab_id">

                                        <div class="row g-3 mb-4">
                                            <div class="col-md-6">
                                                <label for="test_type" class="form-label fw-bold text-dark small text-uppercase">
                                                    Test Type <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-select bg-light" name="test_type" id="test_type" required>
                                                    <option value="">-- Select Type --</option>
                                                    <option value="Practice">Practice Session</option>
                                                    <option value="Weekly Test">Weekly Test</option>
                                                    <option value="Monthly Test">Monthly Assessment</option>
                                                    <option value="Internal Exam">Internal Examination</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="duration" class="form-label fw-bold text-dark small text-uppercase">
                                                    Duration (Minutes)
                                                </label>
                                                <input type="number"
                                                    class="form-control bg-light"
                                                    name="duration"
                                                    id="duration"
                                                    min="0"
                                                    max="180"
                                                    placeholder="0 = Unlimited">
                                            </div>
                                        </div>

                                        <div class="alert alert-light border rounded-3 p-3 mb-4">
                                            <div class="d-flex gap-2">
                                                <i class="bi bi-shield-check text-success fs-5"></i>
                                                <div>
                                                    <h6 class="fw-bold text-dark mb-1">Process Overview</h6>
                                                    <ul class="mb-0 small text-secondary ps-3">
                                                        <li>Students in the allocated lab will be enrolled instantly.</li>
                                                        <li>Unique question sets (2 per student) will be generated.</li>
                                                        <li>Adjacent seating conflict check will be enforced.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-3 pt-2">
                                            <a href="dashboard.php" class="btn btn-light border px-4">Cancel</a>
                                            <button type="submit" class="btn btn-primary px-5 fw-bold shadow-sm flex-grow-1">
                                                <i class="bi bi-rocket-takeoff me-2"></i> Launch Session
                                            </button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('subject_id').addEventListener('change', function() {
        var selected = this.options[this.selectedIndex];
        if (selected.value) {
            var labId = selected.getAttribute('data-lab');
            var qCount = selected.getAttribute('data-questions');
            document.getElementById('lab_id').value = labId;

            var hint = document.getElementById('subject-hint');
            hint.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i> Selected subject has <strong>${qCount}</strong> questions available.`;
            hint.className = 'form-text text-success mt-2';
        }
    });
</script>

<?php include '../includes/footer.php'; ?>