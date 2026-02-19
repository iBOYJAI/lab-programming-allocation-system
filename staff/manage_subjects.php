<?php

/**
 * Manage Subjects - Staff Portal
 * Allows staff to manage their assigned subjects
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['staff'], '../auth.php');

$staff_id = $_SESSION['user_id'];

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
        $lab_id = intval($_POST['lab_id']);

        if (empty($name) || empty($code) || !$lab_id) {
            $_SESSION['error'] = 'All fields are required';
        } else {
            // Start transaction
            $conn = getDbConnection();
            $conn->beginTransaction();
            try {
                // 1. Insert Subject
                $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, language) VALUES (?, ?, ?)");
                $stmt->execute([$name, $code, $lang]);
                $subject_id = $conn->lastInsertId();

                // 2. Assign to Staff
                $stmt = $conn->prepare("INSERT INTO staff_assignments (staff_id, subject_id, lab_id) VALUES (?, ?, ?)");
                $stmt->execute([$staff_id, $subject_id, $lab_id]);

                $conn->commit();
                logActivity($staff_id, 'staff', 'add_subject', "Added subject: $name");
                $_SESSION['success'] = 'Subject added and assigned successfully';
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

        // Verify assignment
        $check = dbQuery("SELECT 1 FROM staff_assignments WHERE staff_id = ? AND subject_id = ?", [$staff_id, $id]);
        if (!empty($check)) {
            $sql = "UPDATE subjects SET subject_name = ?, subject_code = ?, language = ? WHERE id = ?";
            if (dbExecute($sql, [$name, $code, $lang, $id])) {
                logActivity($staff_id, 'staff', 'edit_subject', "Updated subject: $name");
                $_SESSION['success'] = 'Subject updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update subject';
            }
        } else {
            $_SESSION['error'] = 'Unauthorized access';
        }
        redirect('manage_subjects.php');
    } elseif ($action === 'delete') {
        $id = intval($_POST['subject_id']);

        // Verify assignment
        $check = dbQuery("SELECT 1 FROM staff_assignments WHERE staff_id = ? AND subject_id = ?", [$staff_id, $id]);
        if (!empty($check)) {
            $sql = "DELETE FROM subjects WHERE id = ?";
            if (dbExecute($sql, [$id])) {
                logActivity($staff_id, 'staff', 'delete_subject', "Deleted subject ID: $id");
                $_SESSION['success'] = 'Subject deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete subject';
            }
        } else {
            $_SESSION['error'] = 'Unauthorized access';
        }
        redirect('manage_subjects.php');
    }
}

// Fetch assigned subjects with question count
$subjects = dbQuery("
    SELECT s.*, l.lab_name, sa.lab_id, COUNT(q.id) as question_count
    FROM subjects s
    INNER JOIN staff_assignments sa ON s.id = sa.subject_id
    INNER JOIN labs l ON sa.lab_id = l.id
    LEFT JOIN questions q ON s.id = q.subject_id
    WHERE sa.staff_id = ?
    GROUP BY s.id
    ORDER BY s.subject_name
", [$staff_id]);

// Get all labs for the "Add" modal
$labs = dbQuery("SELECT * FROM labs ORDER BY lab_name");

$page_title = 'My Subjects';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
                <div>
                    <h1 class="fw-bold mb-1 text-dark fs-3">My Subjects</h1>
                    <p class="text-secondary mb-0">Manage your assigned subjects and their question banks.</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                    <i class="bi bi-plus-lg me-2"></i> Add Subject
                </button>
            </div>

            <?php echo displayAlert(); ?>

            <div class="card-notion animate-fade-up delay-100">
                <div class="card-notion-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Subject Name</th>
                                    <th>Code</th>
                                    <th>Language</th>
                                    <th>Assigned Lab</th>
                                    <th>Questions</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($subjects)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <p class="text-muted">No assigned subjects found.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark"><?= sanitizeOutput($subject['subject_name']) ?></div>
                                            </td>
                                            <td><span class="badge bg-light text-dark border"><?= sanitizeOutput($subject['subject_code']) ?></span></td>
                                            <td><span class="badge bg-primary bg-opacity-10 text-primary rounded-pill"><?= sanitizeOutput($subject['language']) ?></span></td>
                                            <td><?= sanitizeOutput($subject['lab_name']) ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-bold"><?= $subject['question_count'] ?></span>
                                                    <div class="progress flex-grow-1" style="height: 4px; width: 60px;">
                                                        <div class="progress-bar bg-success" style="width: <?= min(100, $subject['question_count'] * 10) ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-icon btn-ghost-secondary rounded-circle" data-bs-toggle="dropdown">
                                                        <i class="bi bi-three-dots"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="subject_details.php?id=<?= $subject['id'] ?>"><i class="bi bi-eye me-2"></i>View Details</a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="editSubject(<?= htmlspecialchars(json_encode($subject)) ?>)"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                                        <li>
                                                            <hr class="dropdown-divider">
                                                        </li>
                                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteSubject(<?= $subject['id'] ?>, '<?= sanitizeOutput($subject['subject_name']) ?>')"><i class="bi bi-trash me-2"></i>Delete</a></li>
                                                    </ul>
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
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Subject Name</label>
                        <input type="text" name="subject_name" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Code</label>
                            <input type="text" name="subject_code" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Language</label>
                            <select name="language" class="form-select" required>
                                <option value="C">C</option>
                                <option value="C++">C++</option>
                                <option value="Java">Java</option>
                                <option value="Python">Python</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Primary Lab</label>
                        <select name="lab_id" class="form-select" required>
                            <?php foreach ($labs as $lab): ?>
                                <option value="<?= $lab['id'] ?>"><?= sanitizeOutput($lab['lab_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Create & Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="subject_id" id="edit_subject_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Subject Name</label>
                        <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Code</label>
                            <input type="text" name="subject_code" id="edit_subject_code" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Language</label>
                            <select name="language" id="edit_language" class="form-select" required>
                                <option value="C">C</option>
                                <option value="C++">C++</option>
                                <option value="Java">Java</option>
                                <option value="Python">Python</option>
                            </select>
                        </div>
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

<!-- Delete Form -->
<form method="POST" id="deleteForm" style="display:none;">
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
        new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
    }

    function deleteSubject(id, name) {
        if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
            document.getElementById('delete_subject_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
</script>

<?php include '../includes/footer.php'; ?>