<?php

/**
 * View Question Details - Premium Design
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

if (!isset($_GET['id'])) {
    header("Location: manage_questions.php");
    exit;
}

$question_id = intval($_GET['id']);
$conn = getDbConnection();

// Fetch Question Details with Subject
$stmt = $conn->prepare("
    SELECT q.*, s.subject_name, s.subject_code, s.language 
    FROM questions q
    JOIN subjects s ON q.subject_id = s.id
    WHERE q.id = ?
");
$stmt->execute([$question_id]);
$question = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$question) {
    $_SESSION['error'] = "Question not found.";
    header("Location: manage_questions.php");
    exit;
}

$page_title = 'Question Details';
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
                        <a href="manage_questions.php" class="btn btn-white border shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="page-title mb-0 text-dark">Question Details</h1>
                            <p class="text-muted small mb-0 fw-bold font-monospace">Question ID: #<?= str_pad($question['id'], 5, '0', STR_PAD_LEFT) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-warning text-white rounded-pill px-4 shadow-sm fw-bold transition-all" onclick='editQuestion(<?= json_encode($question) ?>)'>
                        <i class="bi bi-pencil-square me-2"></i>Edit Question
                    </button>
                </div>
            </div>

            <div class="row g-4 justify-content-center">
                <div class="col-lg-10">
                    <!-- Question Card -->
                    <div class="card card-notion border-0 shadow-sm mb-4">
                        <div class="card-notion-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-white border text-secondary font-monospace x-small fw-bold px-2 py-1"><?= sanitizeOutput($question['subject_code']) ?></span>
                                        <span class="badge bg-indigo-subtle text-indigo border border-indigo border-opacity-10 rounded-pill x-small fw-bold px-3 py-1"><?= sanitizeOutput($question['language']) ?></span>
                                    </div>
                                    <h3 class="fw-900 text-dark mt-2 mb-0" style="letter-spacing: -0.02em;"><?= sanitizeOutput($question['question_title']) ?></h3>
                                </div>
                            </div>
                        </div>

                        <hr class="text-secondary opacity-5 my-4">

                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-body-text text-muted"></i>
                            <h6 class="text-uppercase text-secondary fw-900 mb-0" style="font-size: 0.65rem; letter-spacing: 0.1em;">Problem Description</h6>
                        </div>
                        <div class="p-4 bg-light bg-opacity-50 rounded-4 border border-dashed">
                            <p class="mb-0 text-dark" style="white-space: pre-wrap; line-height: 1.8; font-size: 0.95rem;"><?= sanitizeOutput($question['question_text']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Additional Info / Metadata -->
                <div class="card card-notion border-0 shadow-sm">
                    <div class="card-notion-body p-4">
                        <div class="d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-info-circle text-muted"></i>
                            <h6 class="text-uppercase text-secondary fw-900 mb-0" style="font-size: 0.65rem; letter-spacing: 0.1em;">Question Info</h6>
                        </div>
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="p-3 bg-light bg-opacity-25 rounded-3 border">
                                    <label class="x-small text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.6rem;">Subject</label>
                                    <span class="fw-bold text-dark small"><?= sanitizeOutput($question['subject_name']) ?></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light bg-opacity-25 rounded-3 border">
                                    <label class="x-small text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.6rem;">Date Created</label>
                                    <span class="fw-bold text-dark small"><?= date('M j, Y • H:i', strtotime($question['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light bg-opacity-25 rounded-3 border">
                                    <label class="x-small text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 0.6rem;">Marks</label>
                                    <span class="fw-900 text-indigo small"><?= $question['marks'] ?? '10' ?> Points</span>
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

<!-- Re-use Edit Modal Script Stub -->
<script>
    function editQuestion(q) {
        // Redirect to manage page to open modal
        // Ideally we pass ID in URL and manage page opens modal automatically, but for now just redirect
        window.location.href = 'manage_questions.php?edit_id=' + q.id;
    }
</script>

<?php include '../includes/footer.php'; ?>