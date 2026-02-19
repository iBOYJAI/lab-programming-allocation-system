<?php

/**
 * Subject Details - Staff Portal - Premium Technical Redesign
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['staff'], '../auth.php');

$subject_id = intval($_GET['id'] ?? 0);
$staff_id = $_SESSION['user_id'];

if (!$subject_id) {
    redirect('dashboard.php');
}

$conn = getDbConnection();

// Fetch Subject Details and verify assignment
$subjStmt = $conn->prepare("
    SELECT s.*, l.lab_name
    FROM subjects s
    INNER JOIN staff_assignments sa ON s.id = sa.subject_id
    INNER JOIN labs l ON sa.lab_id = l.id
    WHERE s.id = ? AND sa.staff_id = ?
");
$subjStmt->execute([$subject_id, $staff_id]);
$subject = $subjStmt->fetch(PDO::FETCH_ASSOC);

if (!$subject) {
    redirect('dashboard.php');
}

// Fetch Questions for this Subject
$qStmt = $conn->prepare("
    SELECT * FROM questions 
    WHERE subject_id = ? 
    ORDER BY created_at DESC
");
$qStmt->execute([$subject_id]);
$questions = $qStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Enrolled Students with their progress
$stuStmt = $conn->prepare("
    SELECT s.id, s.name, s.email, s.gender, s.roll_number, s.department, s.avatar_id,
           (SELECT COUNT(DISTINCT sa_in.session_id) 
            FROM submissions sub_in 
            JOIN student_allocations sa_in ON sub_in.allocation_id = sa_in.id 
            JOIN exam_sessions es_in ON sa_in.session_id = es_in.id 
            WHERE sa_in.student_id = s.id AND es_in.subject_id = ?) as exams_taken,
           (SELECT AVG(CAST(sub_acc.marks AS DECIMAL(10,2)) / r_acc.total_marks * 100) 
            FROM submissions sub_acc 
            JOIN student_allocations sa_acc ON sub_acc.allocation_id = sa_acc.id
            JOIN exam_sessions es_acc ON sa_acc.session_id = es_acc.id 
            LEFT JOIN results r_acc ON (es_acc.id = r_acc.session_id AND sa_acc.student_id = r_acc.student_id)
            WHERE sa_acc.student_id = s.id AND es_acc.subject_id = ?) as avg_accuracy
    FROM students s
    INNER JOIN student_subjects ss ON s.id = ss.student_id
    WHERE ss.subject_id = ?
    ORDER BY s.name ASC
");
$stuStmt->execute([$subject_id, $subject_id, $subject_id]);
$enrolled_students = $stuStmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Node Oversight: ' . $subject['subject_name'];
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <!-- Header -->
            <div class="row align-items-center mb-4 animate-fade-up">
                <div class="col">
                    <div class="d-flex align-items-center gap-3">
                        <a href="manage_subjects.php" class="btn btn-white border shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="page-title mb-0 text-dark">Subject Resource Oversight</h1>
                            <p class="text-muted small mb-0 fw-bold font-monospace">RESOURCE_NODE: <?= sanitizeOutput($subject['subject_code']) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex gap-2">
                        <a href="manage_questions.php?subject_id=<?= $subject['id'] ?>" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
                            <i class="bi bi-plus-lg me-2"></i>Append Repository
                        </a>
                    </div>
                </div>
            </div>

            <!-- Subject Identity Section -->
            <div class="row g-4 mb-4 animate-fade-up">
                <div class="col-lg-8">
                    <div class="card card-notion border-0 shadow-sm h-100">
                        <div class="card-notion-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-4">
                                <div>
                                    <span class="badge bg-indigo-subtle text-indigo border border-indigo border-opacity-10 rounded-pill x-small fw-900 px-3 py-1 mb-2">DOMAIN_ROOT</span>
                                    <h2 class="fw-900 text-dark mb-1" style="letter-spacing: -0.02em;"><?= sanitizeOutput($subject['subject_name']) ?></h2>
                                    <p class="text-secondary small mb-0 fw-bold font-monospace">OBJECT_IDENTIFIER: #<?= str_pad($subject['id'], 5, '0', STR_PAD_LEFT) ?></p>
                                </div>
                                <div class="text-end">
                                    <div class="p-2 bg-light rounded-3 border mb-1">
                                        <i class="bi bi-pc-display text-indigo me-2"></i>
                                        <span class="small fw-bold text-dark"><?= sanitizeOutput($subject['lab_name']) ?></span>
                                    </div>
                                    <span class="x-small text-muted fw-bold text-uppercase">Operational Hub</span>
                                </div>
                            </div>

                            <div class="p-3 bg-light bg-opacity-50 rounded-4 border border-dashed">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="x-small text-muted fw-bold text-uppercase d-block mb-1">Environment</label>
                                        <span class="badge bg-white border text-dark font-monospace x-small fw-bold px-2 py-1"><?= sanitizeOutput($subject['language']) ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="x-small text-muted fw-bold text-uppercase d-block mb-1">Cluster status</label>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 rounded-pill x-small fw-bold px-3 py-1">ACTIVE</span>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="x-small text-muted fw-bold text-uppercase d-block mb-1">Registry</label>
                                        <span class="small fw-bold text-dark font-monospace"><?= date('M j, Y', strtotime($subject['created_at'])) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card card-notion border-0 shadow-sm h-100">
                        <div class="card-notion-body p-4 d-flex flex-column justify-content-center">
                            <div class="text-center mb-4">
                                <div class="display-5 fw-900 text-indigo mb-0"><?= count($enrolled_students) ?></div>
                                <div class="x-small text-muted fw-bold text-uppercase ls-1">Client Subscriptions</div>
                            </div>
                            <div class="d-flex flex-column gap-2 mt-auto">
                                <div class="d-flex justify-content-between align-items-center p-2 border-bottom border-dashed">
                                    <span class="small text-secondary fw-medium">Repository Units</span>
                                    <span class="small fw-bold text-dark"><?= count($questions) ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center p-2 border-bottom border-dashed">
                                    <span class="small text-secondary fw-medium">Avg Accuracy</span>
                                    <?php
                                    $all_acc = array_filter(array_column($enrolled_students, 'avg_accuracy'));
                                    $mean_acc = count($all_acc) > 0 ? array_sum($all_acc) / count($all_acc) : 0;
                                    ?>
                                    <span class="small fw-bold text-dark"><?= number_format($mean_acc, 1) ?>%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabbed Interface -->
            <div class="card card-notion border-0 shadow-sm animate-fade-up delay-100">
                <div class="card-notion-body p-0">
                    <div class="border-bottom overflow-x-auto">
                        <ul class="nav nav-tabs border-0 px-2" role="tablist">
                            <li class="nav-item">
                                <button class="nav-tab-notion nav-link active px-4 py-3 fw-bold small text-uppercase border-0" data-bs-toggle="tab" data-bs-target="#questions-pool" type="button" role="tab" aria-controls="questions-pool" aria-selected="true">
                                    <i class="bi bi-diagram-3 me-2"></i>Resource Pool
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-tab-notion nav-link px-4 py-3 fw-bold small text-uppercase border-0" data-bs-toggle="tab" data-bs-target="#candidates" type="button" role="tab" aria-controls="candidates" aria-selected="false">
                                    <i class="bi bi-people me-2"></i>Candidate Analytics
                                </button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content">
                        <!-- Resource Pool -->
                        <div class="tab-pane fade show active p-0" id="questions-pool" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table notion-table table-hover align-middle mb-0">
                                    <thead class="bg-light bg-opacity-50">
                                        <tr>
                                            <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Structure Node</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Complexity</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Value</th>
                                            <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Command</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($questions)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5">
                                                    <div class="text-muted opacity-50 mb-2"><i class="bi bi-box fs-1"></i></div>
                                                    <div class="text-muted small fw-medium">No resource nodes indexed in this cluster.</div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($questions as $q):
                                                $diff = $q['difficulty'] ?? 'Medium';
                                                $badgeClass = match ($diff) {
                                                    'Easy' => 'bg-success-subtle text-success border-success border-opacity-25',
                                                    'Medium' => 'bg-warning-subtle text-warning border-warning border-opacity-25',
                                                    'Hard' => 'bg-danger-subtle text-danger border-danger border-opacity-25',
                                                    default => 'bg-secondary-subtle text-secondary border-secondary border-opacity-25'
                                                };
                                            ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="fw-bold text-dark"><?= sanitizeOutput($q['question_title']) ?></div>
                                                        <div class="text-muted x-small text-truncate" style="max-width: 350px;"><?= sanitizeOutput(strip_tags($q['question_text'])) ?></div>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $badgeClass ?> border rounded-pill px-3 fw-900 x-small"><?= strtoupper($diff) ?></span>
                                                    </td>
                                                    <td><span class="fw-bold text-indigo x-small"><?= $q['marks'] ?? '10' ?> SEC_POINTS</span></td>
                                                    <td class="pe-4 text-end">
                                                        <a href="manage_questions.php?id=<?= $q['id'] ?>&action=edit" class="btn btn-sm btn-white border rounded-pill shadow-sm">
                                                            <i class="bi bi-pencil-square me-1"></i>Edit Node
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Candidate Analytics -->
                        <div class="tab-pane fade" id="candidates" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table notion-table table-hover align-middle mb-0">
                                    <thead class="bg-light bg-opacity-50">
                                        <tr>
                                            <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Candidate Identity</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Evaluations</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Efficiency Mean</th>
                                            <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Inspect</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($enrolled_students)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5">
                                                    <div class="text-muted opacity-50 mb-2"><i class="bi bi-people fs-1"></i></div>
                                                    <div class="text-muted small fw-medium">No candidates registered for this domain.</div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($enrolled_students as $stu): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <img src="../assets/images/Avatar/<?= getProfileImage($stu['gender'], $stu['id'], $stu['avatar_id'] ?? null) ?>" class="rounded-circle border" width="32" height="32">
                                                            <div>
                                                                <div class="fw-bold text-dark"><?= sanitizeOutput($stu['name']) ?></div>
                                                                <div class="text-muted x-small font-monospace"><?= sanitizeOutput($stu['roll_number']) ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border fw-bold"><?= $stu['exams_taken'] ?> LOGS</span>
                                                    </td>
                                                    <td>
                                                        <?php $acc = $stu['avg_accuracy'] ?: 0; ?>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="progress flex-grow-1" style="height: 6px; width: 60px; background-color: #f0f0f0;">
                                                                <div class="progress-bar bg-indigo" style="width: <?= $acc ?>%"></div>
                                                            </div>
                                                            <span class="fw-bold text-dark x-small"><?= number_format($acc, 1) ?>%</span>
                                                        </div>
                                                    </td>
                                                    <td class="pe-4 text-end">
                                                        <a href="view_student.php?id=<?= $stu['id'] ?>" class="btn btn-sm btn-white border rounded-pill shadow-sm">
                                                            <i class="bi bi-search me-1"></i>Analyze
                                                        </a>
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

<?php include '../includes/footer.php'; ?>