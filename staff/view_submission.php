<?php

/**
 * View & Grade Submission - Staff Portal
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['staff'], '../auth.php');

$submission_id = intval($_GET['id'] ?? 0);
$staff_id = $_SESSION['user_id'];

if (!$submission_id) {
    redirect('evaluate.php');
}

// Handle Form Submission (Grading)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
    if (!validateCSRFToken($_POST['csrf_token'])) {
        die("CSRF Token Verification Failed");
    }

    $marks = floatval($_POST['marks']);
    $remarks = sanitizeInput($_POST['remarks']);

    try {
        $sql = "UPDATE submissions SET marks = ?, remarks = ?, evaluated_by = ?, evaluated_at = NOW() WHERE id = ?";
        dbExecute($sql, [$marks, $remarks, $staff_id, $submission_id]);

        $_SESSION['success'] = "Submission graded successfully!";
        // Refresh to show updated data
        header("Location: view_submission.php?id=$submission_id");
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}

// Fetch Submission Details
$submission = dbQuery("
    SELECT s.*, st.roll_number, st.name as student_name, 
           q.question_title, q.question_text, q.sample_input, q.sample_output, 
           sub.subject_name, sub.language
    FROM submissions s
    INNER JOIN student_allocations sa ON s.allocation_id = sa.id
    INNER JOIN students st ON sa.student_id = st.id
    INNER JOIN questions q ON s.question_id = q.id
    INNER JOIN exam_sessions es ON sa.session_id = es.id
    INNER JOIN subjects sub ON es.subject_id = sub.id
    WHERE s.id = ? AND es.staff_id = ?
", [$submission_id, $staff_id]);

if (empty($submission)) {
    $_SESSION['error'] = "Submission not found or access denied.";
    redirect('evaluate.php');
}

$sub = $submission[0];
$page_title = 'Grade Submission';
// Enable CodeMirror for code viewer
$include_codemirror = true;
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
                <div class="d-flex align-items-center gap-3">
                    <a href="evaluate.php" class="btn btn-light rounded-circle shadow-sm"
                        style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div class="bg-indigo-subtle p-2 rounded-3 text-indigo">
                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-code.svg" width="24"
                            class="notion-icon">
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0">Grade Submission</h4>
                        <p class="text-secondary small mb-0">
                            <?= sanitizeOutput($sub['student_name']) ?> (<?= sanitizeOutput($sub['roll_number']) ?>)
                        </p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge bg-light text-dark border d-flex align-items-center">
                        <i class="bi bi-journal-code me-2"></i> <?= sanitizeOutput($sub['subject_name']) ?>
                    </span>
                </div>
            </div>

            <?php echo displayAlert(); ?>

            <div class="row g-4 h-100">
                <!-- Left Column: Code Viewer -->
                <div class="col-lg-6 animate-fade-up delay-100 h-100">
                    <div class="card-notion h-100 border-0 shadow-sm overflow-hidden d-flex flex-column">
                        <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
                                <span class="avatar-circle-sm bg-blue-subtle text-blue me-2"><i class="bi bi-code-slash"></i></span>
                                Submitted Code
                            </h6>
                            <span class="badge bg-secondary-subtle text-secondary border px-3 rounded-pill fw-normal">
                                <?= sanitizeOutput($sub['language']) ?>
                            </span>
                        </div>
                        <div class="card-notion-body p-0 position-relative flex-grow-1 d-flex flex-column">
                             <div class="code-header-mac d-flex align-items-center p-2 bg-dark border-bottom border-secondary">
                                <div class="window-dots d-flex gap-1 ms-2">
                                    <span class="dot-red"></span>
                                    <span class="dot-yellow"></span>
                                    <span class="dot-green"></span>
                                </div>
                             </div>
                            <textarea id="code-editor" name="code"><?= sanitizeOutput($sub['submitted_code']) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Question & Grading -->
                <div class="col-lg-6 animate-fade-up delay-200">
                    <!-- Question Details -->
                    <div class="card-notion mb-4 border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom p-3">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-help-circle.svg"
                                    width="18" class="opacity-50 me-2">
                                Problem Details
                            </h6>
                        </div>
                        <div class="card-notion-body p-4">
                            <h6 class="fw-bold text-dark mb-2"><?= sanitizeOutput($sub['question_title']) ?></h6>
                            <div
                                class="p-3 bg-light rounded-3 border-start border-4 border-indigo mb-3 text-secondary small">
                                <?= nl2br(sanitizeOutput($sub['question_text'])) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Grading Form -->
                    <div class="card-notion border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom p-3">
                            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-check.svg"
                                    width="18" class="opacity-50 me-2">
                                Assessment
                            </h6>
                        </div>
                        <div class="card-notion-body p-4">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="grade_submission" value="1">

                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-end mb-2">
                                        <label
                                            class="form-label fw-bold text-secondary text-uppercase x-small tracking-wide mb-0">Score</label>
                                        <span class="badge bg-success-subtle text-success">Max: 50</span>
                                    </div>
                                    <div class="input-group input-group-lg">
                                        <input type="number" name="marks" class="form-control fw-bold text-primary"
                                            step="0.5" min="0" max="50" value="<?= $sub['marks'] ?>" required
                                            placeholder="0.0">
                                        <span class="input-group-text bg-light text-muted">/ 50</span>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label
                                        class="form-label fw-bold text-secondary text-uppercase x-small tracking-wide">Feedback
                                        / Remarks</label>
                                    <textarea name="remarks" class="form-control" rows="4"
                                        placeholder="Enter specific feedback for the student..."><?= sanitizeOutput($sub['remarks']) ?></textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit"
                                        class="btn btn-primary rounded-pill py-2 fw-bold shadow-sm hover-scale">
                                        <i class="bi bi-check-circle-fill me-2"></i> Update Grade
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <div class="badge bg-light text-secondary border fw-normal px-3 py-2 rounded-pill">
                            <i class="bi bi-clock me-1"></i> Submitted: <?= formatDate($sub['submitted_at']) ?>
                        </div>
                        <?php if ($sub['evaluated_at']): ?>
                            <div class="mt-2 text-success small fw-bold animate-fade-up">
                                <i class="bi bi-patch-check-fill me-1"></i> Graded on
                                <?= formatDate($sub['evaluated_at']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize CodeMirror
        var textArea = document.getElementById('code-editor');
        if (textArea) {
            var editor = CodeMirror.fromTextArea(textArea, {
                mode: '<?= $sub['language'] === 'python' ? 'python' : 'text/x-c++src' ?>',
                theme: 'monokai',
                lineNumbers: true,
                readOnly: true,
                lineWrapping: true,
                viewportMargin: Infinity
            });
            editor.setSize("100%", "75vh");
        }
    });
</script>

<style>
    .code-header-mac {
        background: #2b2b2b;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    .window-dots span {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
    }

    .dot-red {
        background: #ff5f56;
    }

    .dot-yellow {
        background: #ffbd2e;
    }

    .dot-green {
        background: #27c93f;
    }

    .x-small {
        font-size: 0.75rem;
    }

    .tracking-wide {
        letter-spacing: 0.05em;
    }

    .hover-scale {
        transition: transform 0.2s;
    }

    .hover-scale:hover {
        transform: scale(1.02);
    }
</style>

<?php include '../includes/footer.php'; ?>