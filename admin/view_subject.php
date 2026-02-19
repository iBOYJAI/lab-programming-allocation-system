<?php

/**
 * View Subject Details - Premium Design
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

if (!isset($_GET['id'])) {
    header("Location: manage_subjects.php");
    exit;
}

$subject_id = intval($_GET['id']);
$conn = getDbConnection();

// Fetch Subject Details
$stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subject) {
    $_SESSION['error'] = "Subject not found.";
    header("Location: manage_subjects.php");
    exit;
}

// Fetch Questions for this Subject
$qStmt = $conn->prepare("
    SELECT * FROM questions 
    WHERE subject_id = ? 
    ORDER BY created_at DESC
");
$qStmt->execute([$subject_id]);
$questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Subject Details';
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
                        <a href="manage_subjects.php" class="btn btn-white border shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="page-title mb-0 text-dark"><?= sanitizeOutput($subject['subject_name']) ?></h1>
                            <p class="text-muted small mb-0 fw-bold font-monospace">Subject Code: <?= sanitizeOutput($subject['subject_code']) ?> • <span class="text-indigo"><?= sanitizeOutput($subject['language']) ?></span></p>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-indigo text-black rounded-pill px-4 shadow-sm fw-bold transition-all" onclick="location.href='manage_questions.php?subject_id=<?= $subject['id'] ?>'">
                        <i class="bi bi-plus-circle me-2"></i>Add Questions
                    </button>
                </div>
            </div>

            <!-- Operational Metrics -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card card-notion border-0 shadow-sm h-100">
                        <div class="card-notion-body p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="small text-uppercase fw-900 text-secondary" style="letter-spacing: 0.1em;">Question Count</span>
                                <div class="bg-indigo-subtle text-indigo rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-collection small"></i>
                                </div>
                            </div>
                            <h2 class="fw-900 mb-0 text-dark"><?= count($questions) ?></h2>
                            <p class="text-muted x-small mt-2 mb-0">Total Questions</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-notion border-0 shadow-sm h-100">
                        <div class="card-notion-body p-4">
                            <?php $enrolledCount = dbQuery("SELECT COUNT(*) as total FROM student_subjects WHERE subject_id = ?", [$subject_id])[0]['total']; ?>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="small text-uppercase fw-900 text-secondary" style="letter-spacing: 0.1em;">Enrollments</span>
                                <div class="bg-blue-subtle text-blue rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-people small"></i>
                                </div>
                            </div>
                            <h2 class="fw-900 mb-0 text-dark"><?= $enrolledCount ?></h2>
                            <p class="text-muted x-small mt-2 mb-0">Active Students</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-notion border-0 shadow-sm h-100">
                        <div class="card-notion-body p-4">
                            <?php
                            $labInfo = dbQuery("SELECT l.lab_name FROM labs l JOIN staff_assignments sa ON l.id = sa.lab_id WHERE sa.subject_id = ? LIMIT 1", [$subject_id]);
                            $lab_name = !empty($labInfo) ? $labInfo[0]['lab_name'] : 'NULL_UNASSIGNED';
                            ?>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="small text-uppercase fw-900 text-secondary" style="letter-spacing: 0.1em;">Origin Lab</span>
                                <div class="bg-purple-subtle text-purple rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-cpu small"></i>
                                </div>
                            </div>
                            <h2 class="fw-900 mb-0 text-dark fs-5 text-truncate"><?= sanitizeOutput($lab_name) ?></h2>
                            <p class="text-muted x-small mt-1 mb-0">Subject Lab</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-notion border-0 shadow-sm h-100">
                        <div class="card-notion-body p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="small text-uppercase fw-900 text-secondary" style="letter-spacing: 0.1em;">Visibility</span>
                                <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-eye small"></i>
                                </div>
                            </div>
                            <h2 class="fw-900 mb-0 text-success fs-5">OPERATIONAL</h2>
                            <p class="text-muted x-small mt-1 mb-0">Active in allocation cycle</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resource Personnel -->
            <div class="card card-notion border-0 shadow-sm mb-4 overflow-hidden">
                <div class="card-header bg-white border-bottom p-4">
                    <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge text-indigo"></i>
                        <span class="small text-uppercase fw-bold text-secondary">Assigned Staff</span>
                    </h6>
                </div>
                <div class="card-notion-body p-4">
                    <div class="row g-4">
                        <?php
                        $staffAssignments = dbQuery("
                            SELECT s.name, s.email, s.staff_id, s.id as sid, l.lab_name, s.gender, s.avatar_id
                            FROM staff s
                            JOIN staff_assignments sa ON s.id = sa.staff_id
                            JOIN labs l ON sa.lab_id = l.id
                            WHERE sa.subject_id = ?
                        ", [$subject_id]);

                        if (empty($staffAssignments)): ?>
                            <div class="col-12 text-center py-4">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-users.svg" width="64" class="opacity-25 mb-3">
                                <h6 class="text-muted small fw-medium">No staff assigned to this subject.</h6>
                                <a href="manage_subjects.php?search=<?= urlencode($subject['subject_name']) ?>&action_trigger=edit" class="btn btn-sm btn-white border rounded-pill px-4 mt-2 shadow-sm">Initialize Assign</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($staffAssignments as $st): ?>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center gap-3 p-3 border rounded-4 bg-light bg-opacity-50 transition-all hover-shadow-sm border-dashed">
                                        <img src="../assets/images/Avatar/<?= getProfileImage($st['gender'] ?? 'Male', $st['sid'], $st['avatar_id'] ?? null) ?>" class="rounded-circle border border-white shadow-sm" width="48" height="48">
                                        <div class="overflow-hidden">
                                            <h6 class="fw-bold text-dark mb-0 text-truncate" style="font-size: 0.9rem;"><?= sanitizeOutput($st['name']) ?></h6>
                                            <p class="text-muted x-small mb-0 font-monospace fw-bold"><?= sanitizeOutput($st['staff_id']) ?></p>
                                            <div class="x-small text-indigo mt-1 fw-bold"><i class="bi bi-cpu-fill me-1"></i><?= sanitizeOutput($st['lab_name']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Multi-Threaded Resource Monitoring -->
            <div class="card card-notion border-0 shadow-sm h-100 d-flex flex-column">
                <div class="card-header bg-white border-bottom p-0">
                    <ul class="nav nav-tabs border-bottom-0 px-4" role="tablist">
                        <li class="nav-item">
                            <button class="nav-tab-notion nav-link active py-3 px-4 fw-bold text-uppercase border-0 h-100" data-bs-toggle="tab" data-bs-target="#qbank" type="button" role="tab" aria-controls="qbank" aria-selected="true" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="bi bi-diagram-3 me-2"></i>Question Bank
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-tab-notion nav-link py-3 px-4 fw-bold text-uppercase border-0 h-100" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab" aria-controls="students" aria-selected="false" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="bi bi-people me-2"></i>Enrolled Students
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-notion-body p-0 flex-grow-1">
                    <div class="tab-content h-100">
                        <!-- Question Bank Tab -->
                        <div class="tab-pane fade show active h-100" id="qbank" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table notion-table table-hover align-middle mb-0">
                                    <thead class="bg-light bg-opacity-50">
                                        <tr>
                                            <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Question Detail</th>
                                            <th class="small text-uppercase text-secondary fw-bold text-center">Marks</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Created Date</th>
                                            <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($questions)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5">
                                                    <div class="text-muted opacity-50 mb-2"><i class="bi bi-input-cursor-text fs-1"></i></div>
                                                    <div class="text-muted small fw-medium">No questions found for this subject.</div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($questions as $q): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="fw-bold text-dark"><?= sanitizeOutput($q['question_title']) ?></div>
                                                        <div class="text-muted x-small text-truncate" style="max-width: 300px;"><?= sanitizeOutput($q['question_text']) ?></div>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="fw-bold text-indigo"><?= $q['marks'] ?? '10' ?></span>
                                                    </td>
                                                    <td class="text-muted x-small font-monospace"><?= date('M j, Y', strtotime($q['created_at'])) ?></td>
                                                    <td class="pe-4 text-end">
                                                        <div class="d-flex justify-content-end gap-2">
                                                            <a href="view_question.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold" title="View Full Problem">
                                                                <i class="bi bi-eye text-primary"></i>
                                                            </a>
                                                            <a href="manage_questions.php?search=<?= urlencode(substr($q['question_title'] ?? '', 0, 20)) ?>" class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold" title="Edit in Question Bank">
                                                                <i class="bi bi-pencil text-warning"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Enrolled Students Tab -->
                        <div class="tab-pane fade h-100" id="students" role="tabpanel">
                            <?php
                            $students = dbQuery("
                                SELECT s.* 
                                FROM students s
                                JOIN student_subjects ss ON s.id = ss.student_id
                                WHERE ss.subject_id = ?
                                ORDER BY s.name ASC
                            ", [$subject_id]);
                            ?>
                            <div class="table-responsive">
                                <table class="table notion-table table-hover align-middle mb-0">
                                    <thead class="bg-light bg-opacity-50">
                                        <tr>
                                            <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Student Name</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Roll Number</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Department</th>
                                            <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Registry</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($students)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5">
                                                    <div class="text-muted opacity-50 mb-2"><i class="bi bi-person-exclamation fs-1"></i></div>
                                                    <div class="text-muted small fw-medium">No students enrolled in this subject.</div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($students as $stu): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="fw-bold text-dark"><?= sanitizeOutput($stu['name']) ?></div>
                                                        <div class="x-small text-muted font-monospace"><?= sanitizeOutput($stu['email']) ?></div>
                                                    </td>
                                                    <td><span class="badge bg-white border text-dark font-monospace x-small"><?= sanitizeOutput($stu['roll_number']) ?></span></td>
                                                    <td><span class="badge bg-light text-secondary border rounded-pill px-3 x-small fw-bold"><?= sanitizeOutput($stu['department']) ?></span></td>
                                                    <td class="pe-4 text-end">
                                                        <a href="view_student.php?id=<?= $stu['id'] ?>" class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light transition-all">PROFILE_VIEW</a>
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
    </div>
</div>
</div>
</div>

<?php include '../includes/footer.php'; ?>