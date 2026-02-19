<?php

/**
 * View Exam Result Details - Premium Design
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

if (!isset($_GET['id'])) {
    header("Location: exam_results.php");
    exit;
}

$allocation_id = intval($_GET['id']);
$conn = getDbConnection();

// Fetch Result Details
$query = "
    SELECT sa.*, s.name as student_name, s.roll_number, s.email, s.gender, s.avatar_id,
           sub.subject_name, sub.subject_code, sub.language,
           es.session_code, es.started_at,
           q1.question_title as q1_title, q1.question_text as q1_text, 
           q2.question_title as q2_title, q2.question_text as q2_text
    FROM student_allocations sa
    JOIN students s ON sa.student_id = s.id
    JOIN exam_sessions es ON sa.session_id = es.id
    JOIN subjects sub ON es.subject_id = sub.id
    JOIN questions q1 ON sa.question_1_id = q1.id
    JOIN questions q2 ON sa.question_2_id = q2.id
    WHERE sa.id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$allocation_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    $_SESSION['error'] = "Result record not found.";
    header("Location: exam_results.php");
    exit;
}

// Check Submissions if any (assuming submissions table exists and links via allocation_id)
// Just a placeholder check or simple query if table exists. 
// Based on logs, 'submissions' table exists.
$subQuery = "SELECT * FROM submissions WHERE allocation_id = ? ORDER BY submitted_at DESC";
$subStmt = $conn->prepare($subQuery);
$subStmt->execute([$allocation_id]);
$submissions = $subStmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Result Details';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <!-- Header -->
            <div class="row align-items-end mb-4">
                <div class="col">
                    <div class="d-flex align-items-center gap-3">
                        <a href="exam_results.php" class="btn btn-white border shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="page-title mb-0 text-dark">Result Details</h1>
                            <p class="text-muted small mb-0 font-monospace">ALLOCATION_NODE: #<?= str_pad($result['id'], 5, '0', STR_PAD_LEFT) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex gap-2">
                        <button class="btn btn-white border shadow-sm rounded-pill px-4 fw-bold transition-all hover-bg-light" onclick="window.print()">
                            <i class="bi bi-printer me-2"></i>Export Report
                        </button>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Profile & Metrics -->
                <div class="col-lg-4">
                    <div class="card card-notion border-0 shadow-sm h-100 d-flex flex-column overflow-hidden">
                        <div class="card-notion-body p-4 text-center flex-grow-1">
                            <?php $avatar = getProfileImage($result['gender'] ?? 'Male', $result['student_id'], $result['avatar_id'] ?? null); ?>
                            <div class="mb-4 position-relative d-inline-block">
                                <img src="../assets/images/Avatar/<?= $avatar ?>" class="rounded-circle shadow-lg" width="120" height="120" style="border: 4px solid #fff;">
                                <div class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle shadow-sm" style="width: 20px; height: 20px;"></div>
                            </div>

                            <h4 class="fw-bold text-dark mb-1"><?= sanitizeOutput($result['student_name']) ?></h4>
                            <p class="text-secondary small fw-bold font-monospace mb-4"><?= sanitizeOutput($result['roll_number']) ?></p>

                            <div class="badge bg-light text-dark border px-3 py-2 rounded-pill mb-4 w-100">
                                <i class="bi bi-envelope-fill me-2 opacity-50"></i><?= sanitizeOutput($result['email']) ?>
                            </div>

                            <hr class="text-muted opacity-10 my-4">

                            <div class="text-start">
                                <div class="mb-4">
                                    <label class="small text-muted fw-bold text-uppercase d-block mb-2" style="font-size: 0.65rem; letter-spacing: 0.1em;">Course Context</label>
                                    <div class="p-3 bg-light bg-opacity-50 rounded-3 border-start border-4 border-indigo">
                                        <div class="fw-bold text-dark lh-sm"><?= sanitizeOutput($result['subject_name']) ?></div>
                                        <div class="small text-indigo fw-medium mt-1 font-monospace"><?= sanitizeOutput($result['subject_code']) ?></div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="small text-muted fw-bold text-uppercase d-block mb-2" style="font-size: 0.65rem; letter-spacing: 0.1em;">Session Origin</label>
                                    <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded border">
                                        <span class="badge bg-dark text-white font-monospace"><?= sanitizeOutput($result['session_code']) ?></span>
                                        <span class="small text-muted fw-bold"><?= date('D, M j, Y', strtotime($result['started_at'])) ?></span>
                                    </div>
                                </div>

                                <div class="mt-4 pt-2">
                                    <label class="small text-muted fw-bold text-uppercase d-block mb-2" style="font-size: 0.65rem; letter-spacing: 0.1em;">Evaluation Score</label>
                                    <div class="d-flex align-items-end gap-2">
                                        <span class="display-5 fw-900 text-dark"><?= $result['total_score'] ?? '0' ?></span>
                                        <span class="fs-4 text-muted pb-2">/ 100</span>
                                        <div class="ms-auto mb-2">
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill small fw-bold">AUTHENTICATED</span>
                                        </div>
                                    </div>
                                    <div class="progress mt-2" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: <?= $result['total_score'] ?? 0 ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- questions & Audit Logs -->
                <div class="col-lg-8">
                    <div class="d-flex flex-column gap-4">
                        <!-- Question Breakdown -->
                        <div class="row g-4">
                            <!-- Q1 -->
                            <div class="col-md-6">
                                <div class="card card-notion border-0 shadow-sm h-100">
                                    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                                        <span class="small text-uppercase fw-900 text-secondary" style="letter-spacing: 0.05em;">Structure 01</span>
                                        <span class="badge <?= $result['status_q1'] == 'Submitted' ? 'bg-success-subtle text-success' : 'bg-light text-muted' ?> border rounded-pill">
                                            <?= $result['status_q1'] ?? 'No Records' ?>
                                        </span>
                                    </div>
                                    <div class="card-notion-body p-4">
                                        <h6 class="fw-bold text-dark mb-2"><?= sanitizeOutput($result['q1_title']) ?></h6>
                                        <div class="p-3 bg-light rounded-3 border mb-0" style="max-height: 120px; overflow-y: auto;">
                                            <p class="mb-0 x-small text-secondary lh-base" style="white-space: pre-wrap;"><?= sanitizeOutput($result['q1_text']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Q2 -->
                            <div class="col-md-6">
                                <div class="card card-notion border-0 shadow-sm h-100">
                                    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                                        <span class="small text-uppercase fw-900 text-secondary" style="letter-spacing: 0.05em;">Structure 02</span>
                                        <span class="badge <?= $result['status_q2'] == 'Submitted' ? 'bg-success-subtle text-success' : 'bg-light text-muted' ?> border rounded-pill">
                                            <?= $result['status_q2'] ?? 'No Records' ?>
                                        </span>
                                    </div>
                                    <div class="card-notion-body p-4">
                                        <h6 class="fw-bold text-dark mb-2"><?= sanitizeOutput($result['q2_title']) ?></h6>
                                        <div class="p-3 bg-light rounded-3 border mb-0" style="max-height: 120px; overflow-y: auto;">
                                            <p class="mb-0 x-small text-secondary lh-base" style="white-space: pre-wrap;"><?= sanitizeOutput($result['q2_text']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tactical Submission History -->
                        <div class="card card-notion border-0 shadow-sm flex-grow-1">
                            <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                                    <i class="bi bi-terminal-split text-indigo"></i>
                                    <span class="small text-uppercase fw-bold text-secondary">Code Submission Audit</span>
                                </h6>
                                <span class="badge bg-light text-secondary border rounded-pill px-3"><?= count($submissions) ?> Snapshots</span>
                            </div>
                            <div class="card-notion-body p-0">
                                <?php if (empty($submissions)): ?>
                                    <div class="text-center py-5">
                                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-folder.svg" width="64" class="opacity-25 mb-3">
                                        <h6 class="text-muted">No tactical snapshots found for this allocation.</h6>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table notion-table table-hover align-middle mb-0">
                                            <thead class="bg-light bg-opacity-50">
                                                <tr>
                                                    <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Timestamp</th>
                                                    <th class="small text-uppercase text-secondary fw-bold">Resource Link</th>
                                                    <th class="small text-uppercase text-secondary fw-bold">Verification</th>
                                                    <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Operations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($submissions as $sub): ?>
                                                    <tr>
                                                        <td class="ps-4">
                                                            <div class="small fw-bold text-dark lh-1"><?= formatDate($sub['submitted_at'], 'H:i:s') ?></div>
                                                            <div class="x-small text-muted mt-1"><?= formatDate($sub['submitted_at'], 'M j, Y') ?></div>
                                                        </td>
                                                        <td class="font-monospace small text-indigo fw-bold"><?= $sub['file_path'] ?? 'artifact_main.src' ?></td>
                                                        <td><span class="badge bg-success-subtle text-success border px-2 py-1 rounded-pill x-small">STABLE</span></td>
                                                        <td class="pe-4 text-end">
                                                            <button class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light transition-all"
                                                                onclick="viewCode(<?= htmlspecialchars(json_encode($sub['submitted_code'])) ?>, '<?= formatDate($sub['submitted_at'], 'H:i:s') ?>')">
                                                                Inspect Source
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
        </div>
    </div>
</div>

<!-- Code Inspection Modal - Premium Redesign -->
<div class="modal fade" id="codeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg overflow-hidden bg-dark">
            <div class="modal-header border-bottom border-secondary border-opacity-25 bg-dark text-white py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-circle-sm bg-indigo bg-opacity-25 text-indigo">
                        <i class="bi bi-code-slash"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-800 mb-0">Source Inspector</h6>
                        <p class="x-small text-secondary mb-0 fw-mono" id="modal-meta-label">SNAPSHOT_DETACHED</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Metadata Bar -->
            <div class="bg-black bg-opacity-50 border-bottom border-secondary border-opacity-10 px-4 py-2 d-flex justify-content-between align-items-center">
                <div class="d-flex gap-4">
                    <div class="small">
                        <span class="text-secondary x-small text-uppercase ls-1 fw-bold">Student:</span>
                        <span class="text-white ms-1 fw-bold"><?= sanitizeOutput($result['student_name']) ?></span>
                    </div>
                    <div class="small">
                        <span class="text-secondary x-small text-uppercase ls-1 fw-bold">Session:</span>
                        <span class="text-white ms-1 fw-mono"><?= sanitizeOutput($result['session_code']) ?></span>
                    </div>
                </div>
                <div class="small fw-mono text-white-50" id="modal-timestamp-full"></div>
            </div>

            <div class="modal-body p-0 position-relative" style="background: #0d1117;">
                <pre class="m-0 p-4" style="height: 60vh; overflow-y: auto;"><code id="modal-code" class="text-light" style="font-family: 'JetBrains Mono', 'Fira Code', monospace; font-size: 0.9rem; line-height: 1.6;"></code></pre>

                <!-- Floating Labels -->
                <div class="position-absolute top-0 end-0 p-3 pointer-events-none">
                    <span class="badge bg-dark bg-opacity-75 border border-secondary border-opacity-25 text-secondary px-3 py-2 rounded-pill x-small fw-mono">
                        UTF-8 / <?= strtoupper($result['language']) ?>
                    </span>
                </div>
            </div>

            <div class="modal-footer border-top border-secondary border-opacity-25 p-3 bg-dark">
                <div class="me-auto">
                    <span class="text-secondary x-small fw-mono ls-1">INTEGRITY_VERIFIED_V2</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary px-4 rounded-pill text-white border-secondary border-opacity-50" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-sm btn-outline-indigo px-4 rounded-pill" onclick="copyModalCode()">
                    <i class="bi bi-clipboard me-1"></i> Copy
                </button>
                <button type="button" class="btn btn-sm btn-indigo px-4 rounded-pill" onclick="downloadModalCode()">
                    <i class="bi bi-download me-1"></i> Download Source
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function viewCode(code, time) {
        document.getElementById('modal-code').textContent = code;
        document.getElementById('modal-timestamp-full').textContent = time;
        new bootstrap.Modal(document.getElementById('codeModal')).show();
    }

    function copyModalCode() {
        const code = document.getElementById('modal-code').textContent;
        navigator.clipboard.writeText(code).then(() => {
            showToast('Code copied to clipboard!', 'success');
        });
    }

    function downloadModalCode() {
        const code = document.getElementById('modal-code').textContent;
        const timestamp = document.getElementById('modal-timestamp-full').textContent.replace(/[: ]/g, '_');
        const filename = `submission_${timestamp}.<?= strtolower($result['language']) == 'python' ? 'py' : (strtolower($result['language']) == 'java' ? 'java' : 'c') ?>`;

        const element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(code));
        element.setAttribute('download', filename);
        element.style.display = 'none';
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    }
</script>

<?php include '../includes/footer.php'; ?>