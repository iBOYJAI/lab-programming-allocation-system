<?php

/**
 * Question Repository - Premium Redesign
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

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

        if (empty($subject_id)) {
            $_SESSION['error'] = 'Subject is required';
        } else {
            $conn = getDbConnection();
            $sql = "INSERT INTO questions (question_title, question_text, subject_id) VALUES (?, ?, ?)";
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
                logActivity($_SESSION['user_id'], 'admin', 'bulk_add_questions', "Added $added_count questions via Bulk Add");
                $_SESSION['success'] = "$added_count questions added successfully";
            } else {
                $_SESSION['error'] = "No valid questions were added";
            }
        }
        redirect('manage_questions.php');
    }


    if ($action === 'add') {
        $title = trim($_POST['question_title']);
        $text = trim($_POST['question_text']);
        $subject_id = intval($_POST['subject_id']);

        if (empty($title) || empty($subject_id)) {
            $_SESSION['error'] = 'Title and subject are required';
        } else {
            $sql = "INSERT INTO questions (question_title, question_text, subject_id) VALUES (?, ?, ?)";
            if (dbExecute($sql, [$title, $text, $subject_id])) {
                logActivity($_SESSION['user_id'], 'admin', 'add_question', "Added question: $title");
                $_SESSION['success'] = 'Question added successfully';
            } else {
                $_SESSION['error'] = 'Failed to add question';
            }
        }
        redirect('manage_questions.php');
    } elseif ($action === 'edit') {
        $id = intval($_POST['question_id']);
        $title = trim($_POST['question_title']);
        $text = trim($_POST['question_text']);
        $subject_id = intval($_POST['subject_id']);
        $sql = "UPDATE questions SET question_title = ?, question_text = ?, subject_id = ? WHERE id = ?";
        if (dbExecute($sql, [$title, $text, $subject_id, $id])) {
            logActivity($_SESSION['user_id'], 'admin', 'edit_question', "Updated question ID: $id");
            $_SESSION['success'] = 'Question updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update question';
        }
        redirect('manage_questions.php');
    } elseif ($action === 'delete') {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Please upload a valid CSV file';
        } else {
            $file = $_FILES['csv_file']['tmp_name'];
            $handle = fopen($file, "r");
            $header = fgetcsv($handle);
            $imported = 0;
            $errors = 0;
            $conn = getDbConnection();

            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) < 3) continue;
                $title = trim($data[0]);
                $text = trim($data[1]);
                $subject_id = intval($data[2]);

                if (empty($title) || empty($text) || empty($subject_id)) {
                    $errors++;
                    continue;
                }

                $sql = "INSERT INTO questions (question_title, question_text, subject_id) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                try {
                    if ($stmt->execute([$title, $text, $subject_id])) {
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
            logActivity($_SESSION['user_id'], 'admin', 'import_questions', "Imported $imported questions via CSV");
        }
        redirect('manage_questions.php');
    }
}

// Filters
$search = $_GET['search'] ?? '';
$subject_filter = $_GET['subject'] ?? 'all';
$difficulty_filter = $_GET['difficulty'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(q.question_title LIKE ? OR q.question_text LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($subject_filter !== 'all') {
    $where[] = "q.subject_id = ?";
    $params[] = $subject_filter;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM questions q $where_sql";
$count_result = dbQuery($count_sql, $params);
$total_records = $count_result[0]['total'] ?? 0;
$total_pages = ceil($total_records / $per_page);

// Get questions
$sql = "SELECT q.*, s.subject_name, s.subject_code 
        FROM questions q
        JOIN subjects s ON q.subject_id = s.id
        $where_sql
        ORDER BY s.subject_name, q.question_title
        LIMIT $per_page OFFSET $offset";
$questions = dbQuery($sql, $params);

// Get subjects for filter
$subjects = dbQuery("SELECT * FROM subjects ORDER BY subject_name");

$page_title = 'Question Repository';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
                <div>
                    <h1 class="fw-bold mb-1 text-dark fs-3">Question Repository</h1>
                    <p class="text-secondary mb-0">Manage programming tasks and assessment challenges.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-white border shadow-sm rounded-pill px-4 fw-medium" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-file-earmark-arrow-up me-2"></i>Import CSV
                    </button>
                    <button class="btn btn-info text-white rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#bulkAddModal">
                        <i class="bi bi-collection me-2"></i>Bulk Add
                    </button>
                    <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                        <i class="bi bi-plus-circle me-2"></i>Add Question
                    </button>
                </div>
            </div>

            <?php echo displayAlert(); ?>

            <!-- Filters Bar -->
            <div class="card-notion mb-4 animate-fade-up delay-100">
                <div class="card-notion-body p-3">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-uppercase text-muted">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Title or keywords..." value="<?= sanitizeOutput($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Subject</label>
                            <select name="subject" class="form-select">
                                <option value="all" <?= $subject_filter === 'all' ? 'selected' : '' ?>>All Subjects</option>
                                <?php foreach ($subjects as $subj): ?>
                                    <option value="<?= $subj['id'] ?>" <?= $subject_filter == $subj['id'] ? 'selected' : '' ?>><?= sanitizeOutput($subj['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-secondary w-100">
                                <i class="bi bi-funnel me-2"></i>Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Questions Table -->
            <div class="card-notion animate-fade-up delay-200">
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-collection-play text-primary me-2"></i>
                        <?php if ($subject_filter !== 'all'): ?>
                            Question Set: <span class="text-indigo"><?= sanitizeOutput($subjects[array_search($subject_filter, array_column($subjects, 'id'))]['subject_name'] ?? 'Subject') ?></span>
                        <?php else: ?>
                            All Question Sets
                        <?php endif; ?>
                    </h5>
                    <span class="badge bg-light text-dark border rounded-pill px-3"><?= $total_records ?> Total</span>
                </div>
                <div class="card-notion-body p-0">
                    <?php if (empty($questions)): ?>
                        <div class="text-center py-5">
                            <div class="opacity-25 mb-3">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-question.svg" width="60">
                            </div>
                            <h5 class="text-dark fw-bold mb-2">No questions match your criteria</h5>
                            <p class="text-muted small">Try adjusting your filters or adding a new question.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 text-uppercase text-secondary" style="font-size: 0.75rem;">Question Details</th>
                                        <th class="text-uppercase text-secondary" style="font-size: 0.75rem;">Subject</th>
                                        <th class="text-uppercase text-secondary" style="font-size: 0.75rem;">Date</th>
                                        <th class="text-end pe-4 text-uppercase text-secondary" style="font-size: 0.75rem;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($questions as $q): ?>
                                        <tr>
                                            <td class="ps-4" style="max-width: 350px;">
                                                <div class="d-flex align-items-start gap-3">
                                                    <div class="bg-light rounded p-2 flex-shrink-0">
                                                        <i class="bi bi-file-text text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark mb-1 text-truncate"><?= sanitizeOutput($q['question_title']) ?></div>
                                                        <div class="text-muted small text-truncate" style="max-width: 280px;"><?= truncateText($q['question_text'] ?? '', 80) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border"><?= sanitizeOutput($q['subject_code']) ?></span>
                                            </td>
                                            <td class="text-muted small"><?= formatDate($q['created_at'], 'M d, Y') ?></td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <a href="view_question.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold" title="View Full Problem">
                                                        <i class="bi bi-eye text-primary me-1"></i>View
                                                    </a>
                                                    <button class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold" onclick="editQuestion(<?= htmlspecialchars(json_encode($q)) ?>)" title="Edit Details">
                                                        <i class="bi bi-pencil text-warning me-1"></i>Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold text-danger" onclick="confirmDelete(<?= $q['id'] ?>, '<?= sanitizeOutput($q['question_title']) ?>')" title="Delete">
                                                        <i class="bi bi-trash me-1"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="card-footer bg-white border-top py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted small">
                                        Showing <?= $offset + 1 ?>-<?= min($offset + $per_page, $total_records) ?> of <?= $total_records ?>
                                    </div>
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item"><a class="page-link border-0 text-dark" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&subject=<?= $subject_filter ?>&difficulty=<?= $difficulty_filter ?>">&laquo; Prev</a></li>
                                            <?php endif; ?>

                                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                    <a class="page-link border-0 <?= $i === $page ? 'bg-primary text-white rounded' : 'text-dark' ?> mx-1" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&subject=<?= $subject_filter ?>&difficulty=<?= $difficulty_filter ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item"><a class="page-link border-0 text-dark" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&subject=<?= $subject_filter ?>&difficulty=<?= $difficulty_filter ?>">Next &raquo;</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">New Assessment Challenge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Question Title</label>
                        <input type="text" name="question_title" class="form-control" required placeholder="e.g. Implement Binary Search">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Subject Area</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">Select Subject...</option>
                                <?php foreach ($subjects as $subj): ?>
                                    <option value="<?= $subj['id'] ?>"><?= sanitizeOutput($subj['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Problem Statement</label>
                        <textarea name="question_text" class="form-control" rows="5" required placeholder="Describe the programming problem alongside any input/output constraints..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Add Question</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Question Modal -->
<div class="modal fade" id="editQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">Edit Challenge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="question_id" id="edit_question_id">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Question Title</label>
                        <input type="text" name="question_title" id="edit_question_title" class="form-control" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Subject Area</label>
                            <select name="subject_id" id="edit_subject_id" class="form-select" required>
                                <?php foreach ($subjects as $subj): ?>
                                    <option value="<?= $subj['id'] ?>"><?= sanitizeOutput($subj['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Problem Statement</label>
                        <textarea name="question_text" id="edit_question_text" class="form-control" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form method="POST" id="deleteQuestionForm" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="question_id" id="delete_question_id">
</form>

</div>

<!-- Bulk Add Modal -->
<div class="modal fade" id="bulkAddModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="max-height: 90vh;">
            <form method="POST">
                <div class="modal-header border-bottom px-4 py-3">
                    <div>
                        <h5 class="modal-title fw-bold">Bulk Add Questions</h5>
                        <p class="text-muted small mb-0">Add up to 20 questions at once.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light overflow-auto" style="max-height: calc(90vh - 120px);">
                    <input type="hidden" name="action" value="bulk_add">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="row mb-4 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Select Subject</label>
                            <select name="subject_id" id="bulk_subject_id" class="form-select" required>
                                <option value="">Choose Subject...</option>
                                <?php foreach ($subjects as $subj): ?>
                                    <option value="<?= $subj['id'] ?>" <?= (isset($_GET['subject_id']) && $_GET['subject_id'] == $subj['id']) ? 'selected' : '' ?>><?= sanitizeOutput($subj['subject_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Quantity</label>
                            <div class="input-group">
                                <input type="number" id="bulk_count" class="form-control" value="20" min="1" max="50">
                                <button class="btn btn-secondary" type="button" onclick="generateBulkRows()">Generate</button>
                            </div>
                        </div>
                    </div>

                    <div id="bulk-questions-container" class="row g-3">
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
        generateBulkRows();
    });

    function generateBulkRows() {
        const container = document.getElementById('bulk-questions-container');
        const count = parseInt(document.getElementById('bulk_count').value) || 20;
        if (!container) return;

        container.innerHTML = '';
        for (let i = 0; i < count; i++) {
            const col = document.createElement('div');
            col.className = 'col-md-6 col-lg-4';
            col.innerHTML = `
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-light text-dark border small">Box ${i + 1}</span>
                        </div>
                        <input type="text" name="questions[${i}][title]" class="form-control form-control-sm mb-2 fw-bold" placeholder="Title">
                        <textarea name="questions[${i}][text]" class="form-control form-control-sm" rows="3" placeholder="Description..."></textarea>
                    </div>
                </div>
            `;
            container.appendChild(col);
        }
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
        document.getElementById('edit_question_id').value = q.id;
        document.getElementById('edit_question_title').value = q.question_title;
        document.getElementById('edit_question_text').value = q.question_text || '';
        document.getElementById('edit_subject_id').value = q.subject_id;
        new bootstrap.Modal(document.getElementById('editQuestionModal')).show();
    }

    function confirmDelete(id, title) {
        if (confirm('Delete question "' + title + '"?\n\nThis cannot be undone!')) {
            document.getElementById('delete_question_id').value = id;
            document.getElementById('deleteQuestionForm').submit();
        }
    }

    // Auto-trigger edit if edit_id is provided
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const editId = urlParams.get('edit_id');
        if (editId) {
            // Find and click the edit button for this ID or fetch data
            // For now, if it's in the current list, edit it.
            // If not in the list, we might need a separate fetch, but usually redirections are to the filtered list.
            <?php foreach ($questions as $q): ?>
                if (editId == '<?= $q['id'] ?>') {
                    editQuestion(<?= json_encode($q) ?>);
                }
            <?php endforeach; ?>
        }
    });
</script>

<?php include '../includes/footer.php'; ?>