<?php

/**
 * Manage Questions - Staff Portal
 * Allows staff to manage questions for their assigned subjects
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['staff'], '../auth.php');

$staff_id = $_SESSION['user_id'];

// Fetch assigned subjects
$assigned_subjects = dbQuery("
    SELECT s.* 
    FROM subjects s
    INNER JOIN staff_assignments sa ON s.id = sa.subject_id
    WHERE sa.staff_id = ? 
    ORDER BY s.subject_name
", [$staff_id]);
$assigned_ids = array_column($assigned_subjects, 'id');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token';
        redirect('manage_questions.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'bulk_add') {
        $subject_id = intval($_POST['subject_id']);
        $questions = $_POST['questions'] ?? [];
        $added_count = 0;

        if (empty($subject_id) || !in_array($subject_id, $assigned_ids)) {
            $_SESSION['error'] = 'Unauthorized or missing subject';
        } else {
            $conn = getDbConnection();
            $sql = "INSERT INTO questions (question_title, question_text, subject_id, difficulty) VALUES (?, ?, ?, 'Normal')";
            $stmt = $conn->prepare($sql);

            foreach ($questions as $q) {
                $title = trim($q['title'] ?? '');
                $text = trim($q['text'] ?? '');

                if (!empty($title) && !empty($text)) {
                    if ($stmt->execute([$title, $text, $subject_id])) {
                        $added_count++;
                    }
                }
            }

            if ($added_count > 0) {
                logActivity($staff_id, 'staff', 'bulk_add_questions', "Added $added_count questions via Bulk Add");
                $_SESSION['success'] = "$added_count questions added successfully";
            } else {
                $_SESSION['error'] = "No valid questions were added";
            }
        }
        redirect('manage_questions.php');
    }

    if ($action === 'add') {
        $title = trim($_POST['question_title']);
        if (in_array($subject_id, $assigned_ids)) {
            $sql = "INSERT INTO questions (question_title, question_text, subject_id, difficulty) VALUES (?, ?, ?, 'Normal')";
            if (dbExecute($sql, [$title, $text, $subject_id])) {
                logActivity($staff_id, 'staff', 'add_question', "Added question: $title");
                $_SESSION['success'] = 'Question added successfully';
            } else {
                $_SESSION['error'] = 'Failed to add question';
            }
        } else {
            $_SESSION['error'] = 'Unauthorized subject';
        }
        redirect('manage_questions.php');
    } elseif ($action === 'edit') {
        $id = intval($_POST['question_id']);
        $title = trim($_POST['question_title']);
        $text = trim($_POST['question_text']);
        $subject_id = intval($_POST['subject_id']);

        // Verify ownership of both question and target subject
        $q_check = dbQuery("SELECT subject_id FROM questions WHERE id = ?", [$id]);
        if (!empty($q_check) && in_array($q_check[0]['subject_id'], $assigned_ids) && in_array($subject_id, $assigned_ids)) {
            $sql = "UPDATE questions SET question_title = ?, question_text = ?, subject_id = ? WHERE id = ?";
            if (dbExecute($sql, [$title, $text, $subject_id, $id])) {
                logActivity($staff_id, 'staff', 'edit_question', "Updated question ID: $id");
                $_SESSION['success'] = 'Question updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update question';
            }
        } else {
            $_SESSION['error'] = 'Unauthorized access';
        }
        redirect('manage_questions.php');
    } elseif ($action === 'delete') {
        $id = intval($_POST['question_id']);
        $q_check = dbQuery("SELECT subject_id FROM questions WHERE id = ?", [$id]);
        if (!empty($q_check) && in_array($q_check[0]['subject_id'], $assigned_ids)) {
            if (dbExecute("DELETE FROM questions WHERE id = ?", [$id])) {
                logActivity($staff_id, 'staff', 'delete_question', "Deleted question ID: $id");
                $_SESSION['success'] = 'Question deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete question';
            }
        } else {
            $_SESSION['error'] = 'Unauthorized access';
        }
        redirect('manage_questions.php');
    } elseif ($action === 'import_csv') {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Please upload a valid CSV file';
        } else {
            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, "r");
            $header = fgetcsv($handle);
            $imported = 0;
            $errors = 0;

            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) < 4) continue;
                $title = trim($data[0]);
                $text = trim($data[1]);
                $subj_id = intval($data[2]);

                if (empty($title) || empty($text) || !in_array($subj_id, $assigned_ids)) {
                    $errors++;
                    continue;
                }

                $sql = "INSERT INTO questions (question_title, question_text, subject_id, difficulty) VALUES (?, ?, ?, 'Normal')";
                try {
                    if (dbExecute($sql, [$title, $text, $subj_id])) {
                        $imported++;
                    } else {
                        $errors++;
                    }
                } catch (Exception $e) {
                    $errors++;
                }
            }
            fclose($handle);
            $_SESSION['success'] = "Import complete! $imported questions added." . ($errors > 0 ? " ($errors skipped)" : "");
            logActivity($staff_id, 'staff', 'import_questions', "Imported $imported questions via CSV");
        }
        redirect('manage_questions.php');
    }
}

// Filters
$search = $_GET['search'] ?? '';
$subject_filter = $_GET['subject'] ?? 'all';

// Build query
$where = ["q.subject_id IN (" . (empty($assigned_ids) ? "0" : implode(',', $assigned_ids)) . ")"];
$params = [];

if (!empty($search)) {
    $where[] = "(q.question_title LIKE ? OR q.question_text LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
}

if ($subject_filter !== 'all' && in_array($subject_filter, $assigned_ids)) {
    $where[] = "q.subject_id = ?";
    $params[] = $subject_filter;
}

$where_sql = "WHERE " . implode(" AND ", $where);

$questions = dbQuery("
    SELECT q.*, s.subject_name, s.subject_code 
    FROM questions q
    INNER JOIN subjects s ON q.subject_id = s.id
    $where_sql
    ORDER BY s.subject_name, q.question_title
", $params);

$page_title = 'Question Bank';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
                <div>
                    <h1 class="fw-bold mb-1 text-dark fs-3">Question Bank</h1>
                    <p class="text-secondary mb-0">Manage programming challenges for your assigned subjects.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-white border rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-file-earmark-arrow-up me-2"></i> Import CSV
                    </button>
                    <button class="btn btn-info text-white rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#bulkAddModal">
                        <i class="bi bi-collection me-2"></i> Bulk Add
                    </button>
                    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-lg me-2"></i> Add Question
                    </button>
                </div>
            </div>

            <?php echo displayAlert(); ?>

            <!-- Filters -->
            <div class="card-notion mb-4 animate-fade-up delay-100">
                <div class="card-notion-body p-3">
                    <form class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Keywords..." value="<?= sanitizeOutput($search) ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Subject</label>
                            <select name="subject" class="form-select">
                                <option value="all">All My Subjects</option>
                                <?php foreach ($assigned_subjects as $subj): ?>
                                    <option value="<?= $subj['id'] ?>" <?= $subject_filter == $subj['id'] ? 'selected' : '' ?>><?= sanitizeOutput($subj['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-secondary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card-notion animate-fade-up delay-200">
                <div class="card-notion-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Title</th>
                                    <th>Subject</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($questions)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">No questions found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($questions as $q): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= sanitizeOutput($q['question_title']) ?></div>
                                                <div class="small text-muted text-truncate" style="max-width: 300px;"><?= truncateText($q['question_text'], 60) ?></div>
                                            </td>
                                            <td><?= sanitizeOutput($q['subject_code']) ?></td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <button class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold" onclick="editQuestion(<?= htmlspecialchars(json_encode($q, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)" title="Edit Details">
                                                        <i class="bi bi-pencil text-warning me-1"></i>Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold text-danger" onclick="deleteQuestion(<?= $q['id'] ?>, '<?= sanitizeOutput($q['question_title']) ?>')" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
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

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">New Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Title</label>
                        <input type="text" name="question_title" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Subject</label>
                            <select name="subject_id" class="form-select" required>
                                <?php foreach ($assigned_subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= sanitizeOutput($s['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label small fw-bold">Question Description</label>
                        <textarea name="question_text" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save Question</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="question_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Title</label>
                        <input type="text" name="question_title" id="edit_title" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold">Subject</label>
                            <select name="subject_id" id="edit_subject_id" class="form-select" required>
                                <?php foreach ($assigned_subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= sanitizeOutput($s['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label small fw-bold">Question Description</label>
                        <textarea name="question_text" id="edit_text" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="question_id" id="delete_id">
</form>

<!-- Bulk Add Modal -->
<div class="modal fade" id="bulkAddModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4" style="height: 90vh;">
            <form method="POST">
                <div class="modal-header border-bottom px-4 py-3">
                    <div>
                        <h5 class="modal-title fw-bold">Bulk Add Questions</h5>
                        <p class="text-muted small mb-0">Add up to 20 questions at once to your assigned subjects.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <input type="hidden" name="action" value="bulk_add">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Select Subject</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">Choose Subject...</option>
                                <?php foreach ($assigned_subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= sanitizeOutput($s['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div id="bulk-questions-container">
                        <!-- Rows will be generated here -->
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save All Questions</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('bulk-questions-container')) {
            for (let i = 0; i < 15; i++) {
                addBulkRow(i);
            }
        }
    });

    function addBulkRow(index) {
        const container = document.getElementById('bulk-questions-container');
        if (!container) return;

        const row = document.createElement('div');
        row.className = 'card border-0 shadow-sm mb-3';
        row.innerHTML = `
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge bg-light text-dark border">Question ${index + 1}</span>
                </div>
                <div class="row g-2">
                    <div class="col-md-12">
                        <input type="text" name="questions[${index}][title]" class="form-control form-control-sm mb-2" placeholder="Question Title (Required)">
                    </div>
                    <div class="col-md-12">
                        <textarea name="questions[${index}][text]" class="form-control form-control-sm" rows="2" placeholder="Full Question Description..."></textarea>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(row);
    }
</script>

<!-- Modal: Import CSV -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Bulk Import Questions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="import_csv">

                    <div class="alert bg-blue-subtle text-blue border-0 small mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Please upload a CSV file with:
                        <code class="d-block mt-1">title, text, subject_id</code>
                        <span class="d-block mt-1 text-muted small">Only subjects assigned to you will be imported.</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Select CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Upload & Process</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editQuestion(q) {
        document.getElementById('edit_id').value = q.id;
        document.getElementById('edit_title').value = q.question_title;
        document.getElementById('edit_text').value = q.question_text || '';
        document.getElementById('edit_subject_id').value = q.subject_id;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }

    function deleteQuestion(id, title) {
        if (confirm(`Delete question: ${title}?`)) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }

    // Auto-trigger edit if requested via URL
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const editId = urlParams.get('id');
        const action = urlParams.get('action');

        if (editId && action === 'edit') {
            const questions = <?= json_encode($questions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
            const q = questions.find(item => item.id == editId);
            if (q) {
                editQuestion(q);
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>