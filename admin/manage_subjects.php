<?php

/**
 * Manage Subjects - Premium Redesign
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
        redirect('manage_subjects.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['subject_name']);
        $code = trim($_POST['subject_code']);
        $lang = $_POST['language'];
        $lab_id = !empty($_POST['lab_id']) ? intval($_POST['lab_id']) : null;
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        $staff_ids = isset($_POST['staff_ids']) ? $_POST['staff_ids'] : [];

        if (empty($name) || empty($code) || empty($lab_id)) {
            $_SESSION['error'] = 'Subject name, code, and primary lab are required';
        } else {
            $conn = getDbConnection();
            $conn->beginTransaction();
            try {
                $sql = "INSERT INTO subjects (subject_name, subject_code, language, lab_id, department_id) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$name, $code, $lang, $lab_id, $department_id]);
                $subject_id = $conn->lastInsertId();

                // Handle Staff Assignments
                if (!empty($staff_ids)) {
                    $assignSql = "INSERT INTO staff_assignments (staff_id, lab_id, subject_id) VALUES (?, ?, ?)";
                    $assignStmt = $conn->prepare($assignSql);
                    foreach ($staff_ids as $sid) {
                        $assignStmt->execute([$sid, $lab_id, $subject_id]);
                    }
                }

                $conn->commit();
                logActivity($_SESSION['user_id'], 'admin', 'add_subject', "Added subject: $name");
                $_SESSION['success'] = 'Subject added successfully';
            } catch (Exception $e) {
                $conn->rollBack();
                $_SESSION['error'] = 'Failed to add subject: ' . $e->getMessage();
            }
        }
        redirect('manage_subjects.php');
    } elseif ($action === 'edit') {
        $id = intval($_POST['subject_id']);
        $name = trim($_POST['subject_name']);
        $code = trim($_POST['subject_code']);
        $lang = $_POST['language'];
        $lab_id = !empty($_POST['lab_id']) ? intval($_POST['lab_id']) : null;
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
        $staff_ids = isset($_POST['staff_ids']) ? $_POST['staff_ids'] : [];

        $conn = getDbConnection();
        $conn->beginTransaction();
        try {
            // Update Subject
            $sql = "UPDATE subjects SET subject_name = ?, subject_code = ?, language = ?, lab_id = ?, department_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $code, $lang, $lab_id, $department_id, $id]);

            // Update Staff Assignments (Delete all for this subject, then re-insert)
            $conn->prepare("DELETE FROM staff_assignments WHERE subject_id = ?")->execute([$id]);

            if (!empty($staff_ids)) {
                $assignSql = "INSERT INTO staff_assignments (staff_id, lab_id, subject_id) VALUES (?, ?, ?)";
                $assignStmt = $conn->prepare($assignSql);
                foreach ($staff_ids as $sid) {
                    $assignStmt->execute([$sid, $lab_id, $id]);
                }
            }

            $conn->commit();
            logActivity($_SESSION['user_id'], 'admin', 'edit_subject', "Updated subject: $name");
            $_SESSION['success'] = 'Subject updated successfully';
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error'] = 'Failed to update subject: ' . $e->getMessage();
        }
        redirect('manage_subjects.php');
    } elseif ($action === 'delete') {
        $id = intval($_POST['subject_id']);
        $sql = "DELETE FROM subjects WHERE id = ?";
        if (dbExecute($sql, [$id])) {
            logActivity($_SESSION['user_id'], 'admin', 'delete_subject', "Deleted subject ID: $id");
            $_SESSION['success'] = 'Subject deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete subject';
        }
        redirect('manage_subjects.php');
    } elseif ($action === 'bulk_delete') {
        $ids = $_POST['selected_ids'] ?? [];
        if (empty($ids)) {
            $_SESSION['error'] = 'No subjects selected for deletion.';
        } else {
            $conn = getDbConnection();
            $conn->beginTransaction();
            try {
                $deleted_count = 0;
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sql = "DELETE FROM subjects WHERE id IN ($placeholders)";
                $stmt = $conn->prepare($sql);
                if ($stmt->execute($ids)) {
                    $deleted_count = $stmt->rowCount();
                }
                $conn->commit();
                logActivity($_SESSION['user_id'], 'admin', 'bulk_delete_subjects', "Bulk deleted $deleted_count subjects");
                $_SESSION['success'] = "$deleted_count subject(s) deleted successfully.";
            } catch (Exception $e) {
                $conn->rollBack();
                $_SESSION['error'] = 'Failed to delete subjects: ' . $e->getMessage();
            }
        }
        redirect('manage_subjects.php');
    }
}

// Fetch all subjects with question count
$subjects = dbQuery("
    SELECT s.*, COUNT(q.id) as question_count
    FROM subjects s
    LEFT JOIN questions q ON s.id = q.subject_id
    GROUP BY s.id
    ORDER BY s.subject_name
");

// Fetch Labs, Departments, and Staff for dropdowns
$labs = dbQuery("SELECT id, lab_name FROM labs ORDER BY lab_name");
$departments = dbQuery("SELECT id, department_name, department_code FROM departments ORDER BY department_name");
$staff_list = dbQuery("SELECT id, name, staff_id FROM staff WHERE status = 'Active' ORDER BY name");

$page_title = 'Manage Subjects';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
                <div>
                    <h1 class="fw-bold mb-1 text-dark fs-3">Course Catalog</h1>
                    <p class="text-secondary mb-0">Manage academic subjects and programming languages.</p>
                </div>
                <div class="d-flex gap-2">
                    <button id="bulkDeleteBtn" class="btn btn-danger rounded-pill px-4 shadow-sm" disabled onclick="confirmBulkDelete()">
                        <i class="bi bi-trash me-2"></i>Bulk Delete
                    </button>
                    <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="bi bi-journal-plus me-2"></i> Add New Subject
                    </button>
                </div>
            </div>

            <?php echo displayAlert(); ?>

            <div class="card-notion animate-fade-up delay-100">
                <div class="card-notion-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 text-uppercase text-secondary" style="font-size:0.75rem; width: 40px;">
                                        <input type="checkbox" id="selectAll" class="form-check-input" onchange="toggleSelectAll()">
                                    </th>
                                    <th class="text-uppercase text-secondary" style="font-size:0.75rem;">Subject Details</th>
                                    <th class="text-uppercase text-secondary" style="font-size:0.75rem;">Code</th>
                                    <th class="text-uppercase text-secondary" style="font-size:0.75rem;">Language</th>
                                    <th class="text-uppercase text-secondary" style="font-size:0.75rem;">Question Bank</th>
                                    <th class="text-end pe-4 text-uppercase text-secondary" style="font-size:0.75rem;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subjects)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="opacity-25 mb-3">
                                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-collection.svg" width="60">
                                            </div>
                                            <p class="text-muted small">No subjects found in the catalog.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <input type="checkbox" class="form-check-input row-checkbox" value="<?= $subject['id'] ?>" onchange="updateBulkDeleteButton()">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-white border rounded-circle p-1 d-flex align-items-center justify-content-center shadow-sm" style="width: 36px; height: 36px;">
                                                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-timeline.svg" width="18" class="opacity-50" loading="lazy" alt="Subject">
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= sanitizeOutput($subject['subject_name']) ?></div>
                                                        <small class="text-muted">Added <?= formatDate($subject['created_at'], 'M Y') ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-light text-dark border font-monospace"><?= sanitizeOutput($subject['subject_code']) ?></span></td>
                                            <td>
                                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 rounded-pill"><?= sanitizeOutput($subject['language']) ?></span>
                                            </td>
                                            <td style="width: 200px;">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-bold small"><?= $subject['question_count'] ?></span>
                                                    <div class="progress flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar <?= $subject['question_count'] >= 15 ? 'bg-success' : 'bg-warning' ?>"
                                                            style="width: <?= min(100, ($subject['question_count'] / 15) * 100) ?>%"></div>
                                                    </div>
                                                    <?php if ($subject['question_count'] < 15): ?>
                                                        <i class="bi bi-exclamation-circle-fill text-warning" data-bs-toggle="tooltip" title="Too few questions (Min 15 required for exams)"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <a href="view_subject.php?id=<?= $subject['id'] ?>" class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold" title="View Details">
                                                        <i class="bi bi-eye text-primary me-1"></i>View
                                                    </a>
                                                    <button class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold" onclick="editSubject(<?= htmlspecialchars(json_encode($subject)) ?>)" title="Edit Subject">
                                                        <i class="bi bi-pencil text-warning me-1"></i>Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold text-danger" onclick="deleteSubject(<?= $subject['id'] ?>, '<?= sanitizeOutput($subject['subject_name']) ?>')" title="Delete">
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

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">Register Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Subject Name *</label>
                        <input type="text" name="subject_name" class="form-control" required placeholder="e.g. Object Oriented Programming">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Code *</label>
                            <input type="text" name="subject_code" class="form-control" required placeholder="e.g. CS202">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Language *</label>
                            <select name="language" class="form-select" required>
                                <option value="">Select...</option>
                                <option value="C">C</option>
                                <option value="C++">C++</option>
                                <option value="Java">Java</option>
                                <option value="Python">Python</option>
                                <option value="HTML">HTML</option>
                                <option value="SQL">SQL</option>
                                <option value="JavaScript">JavaScript</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= sanitizeOutput($d['department_name']) ?> (<?= sanitizeOutput($d['department_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Primary Lab *</label>
                            <select name="lab_id" class="form-select" required>
                                <option value="">Select Lab...</option>
                                <?php foreach ($labs as $lab): ?>
                                    <option value="<?= $lab['id'] ?>"><?= sanitizeOutput($lab['lab_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Assign Staff</label>
                            <div class="border rounded p-3" style="max-height: 150px; overflow-y: auto;">
                                <?php foreach ($staff_list as $st): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="staff_ids[]" value="<?= $st['id'] ?>" id="add_staff_<?= $st['id'] ?>">
                                        <label class="form-check-label small" for="add_staff_<?= $st['id'] ?>">
                                            <?= sanitizeOutput($st['name']) ?> (<?= sanitizeOutput($st['staff_id']) ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Create Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">Modify Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="subject_id" id="edit_subject_id">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Subject Name</label>
                        <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Code</label>
                            <input type="text" name="subject_code" id="edit_subject_code" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Language</label>
                            <select name="language" id="edit_language" class="form-select" required>
                                <option value="C">C</option>
                                <option value="C++">C++</option>
                                <option value="Java">Java</option>
                                <option value="Python">Python</option>
                                <option value="HTML">HTML</option>
                                <option value="SQL">SQL</option>
                                <option value="JavaScript">JavaScript</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Department</label>
                            <select name="department_id" id="edit_department_id" class="form-select">
                                <option value="">None</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= sanitizeOutput($d['department_name']) ?> (<?= sanitizeOutput($d['department_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Primary Lab</label>
                            <select name="lab_id" id="edit_lab_id" class="form-select" required>
                                <option value="">Select Lab...</option>
                                <?php foreach ($labs as $lab): ?>
                                    <option value="<?= $lab['id'] ?>"><?= sanitizeOutput($lab['lab_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Assign Staff</label>
                            <div class="border rounded p-3" style="max-height: 150px; overflow-y: auto;" id="edit_staff_container">
                                <?php foreach ($staff_list as $st): ?>
                                    <div class="form-check">
                                        <input class="form-check-input edit-staff-checkbox" type="checkbox" name="staff_ids[]" value="<?= $st['id'] ?>" id="edit_staff_<?= $st['id'] ?>">
                                        <label class="form-check-label small" for="edit_staff_<?= $st['id'] ?>">
                                            <?= sanitizeOutput($st['name']) ?> (<?= sanitizeOutput($st['staff_id']) ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
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
<form method="POST" id="deleteSubjectForm" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="subject_id" id="delete_subject_id">
</form>

<script>
    function editSubject(subject) {
        document.getElementById('edit_subject_id').value = subject.id;
        document.getElementById('edit_subject_name').value = subject.subject_name;
        document.getElementById('edit_subject_code').value = subject.subject_code;
        document.getElementById('edit_language').value = subject.language;
        document.getElementById('edit_lab_id').value = subject.lab_id;
        document.getElementById('edit_department_id').value = subject.department_id || '';

        // Reset checkboxes
        document.querySelectorAll('.edit-staff-checkbox').forEach(cb => cb.checked = false);

        // Fetch assigned staff
        fetch('get_subject_staff.php?subject_id=' + subject.id)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.staff_ids) {
                    data.staff_ids.forEach(sid => {
                        const cb = document.getElementById('edit_staff_' + sid);
                        if (cb) cb.checked = true;
                    });
                }
            });

        new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
    }

    function deleteSubject(id, name) {
        if (confirm('Delete subject "' + name + '"?\n\nThis will also remove all questions and historical references!')) {
            document.getElementById('delete_subject_id').value = id;
            document.getElementById('deleteSubjectForm').submit();
        }
    }

    // Auto-trigger edit if requested via URL
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action_trigger') === 'edit') {
            <?php if (!empty($subjects)): ?>
                editSubject(<?= json_encode($subjects[0]) ?>);
            <?php endif; ?>
        }
    });

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
        updateBulkDeleteButton();
    }

    function updateBulkDeleteButton() {
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        bulkDeleteBtn.disabled = checkboxes.length === 0;
    }

    function confirmBulkDelete() {
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        const count = checkboxes.length;
        if (count === 0) return;

        document.getElementById('bulk_delete_count').textContent = count;
        const idsContainer = document.getElementById('bulkDeleteIds');
        idsContainer.innerHTML = '';
        
        checkboxes.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_ids[]';
            input.value = cb.value;
            idsContainer.appendChild(input);
        });

        const modal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));
        modal.show();
    }
</script>

<!-- Bulk Delete Modal -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center pt-5 pb-4 px-4">
                <div class="mb-3 text-danger">
                    <i class="bi bi-exclamation-circle display-1"></i>
                </div>
                <h5 class="fw-bold mb-2">Delete Selected Subjects?</h5>
                <p class="text-muted mb-4 opacity-75">Are you sure you want to delete <strong id="bulk_delete_count" class="text-dark">0</strong> selected subject(s)? This cannot be undone.</p>

                <form method="POST" id="bulkDeleteForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="bulk_delete">
                    <div id="bulkDeleteIds"></div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger rounded-pill">Yes, Delete Them</button>
                        <button type="button" class="btn btn-light text-muted rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>