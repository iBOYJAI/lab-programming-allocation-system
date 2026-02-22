<?php

/**
 * Department Management - Premium Design
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
        redirect('manage_departments.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['department_name']);
        $code = trim($_POST['department_code']);
        $description = trim($_POST['description'] ?? '');

        if (empty($name) || empty($code)) {
            $_SESSION['error'] = 'Department name and code are required';
        } else {
            $sql = "INSERT INTO departments (department_name, department_code, description) VALUES (?, ?, ?)";
            if (dbExecute($sql, [$name, $code, $description])) {
                logActivity($_SESSION['user_id'], 'admin', 'add_department', "Added department: $name");
                $_SESSION['success'] = 'Department added successfully';
            } else {
                $_SESSION['error'] = 'Failed to add department';
            }
        }
        redirect('manage_departments.php');
    } elseif ($action === 'edit') {
        $id = intval($_POST['department_id']);
        $name = trim($_POST['department_name']);
        $code = trim($_POST['department_code']);
        $description = trim($_POST['description'] ?? '');

        $sql = "UPDATE departments SET department_name = ?, department_code = ?, description = ? WHERE id = ?";
        if (dbExecute($sql, [$name, $code, $description, $id])) {
            logActivity($_SESSION['user_id'], 'admin', 'edit_department', "Updated department: $name");
            $_SESSION['success'] = 'Department updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update department';
        }
        redirect('manage_departments.php');
    } elseif ($action === 'delete') {
        $id = intval($_POST['department_id']);
        $sql = "DELETE FROM departments WHERE id = ?";
        if (dbExecute($sql, [$id])) {
            logActivity($_SESSION['user_id'], 'admin', 'delete_department', "Deleted department ID: $id");
            $_SESSION['success'] = 'Department deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete department';
        }
        redirect('manage_departments.php');
    }
}

// Fetch all departments with counts
$departments = dbQuery("
    SELECT d.*, 
           (SELECT COUNT(*) FROM staff WHERE department_id = d.id) as staff_count,
           (SELECT COUNT(*) FROM students WHERE department_id = d.id) as student_count,
           (SELECT COUNT(*) FROM subjects WHERE department_id = d.id) as subject_count
    FROM departments d
    ORDER BY d.department_name
");

$page_title = 'Department Management';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
                <div>
                    <h1 class="fw-bold mb-1 text-dark fs-3">Department Management</h1>
                    <p class="text-secondary mb-0">Organize staff, students, and subjects by department.</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                    <i class="bi bi-building me-2"></i> Add Department
                </button>
            </div>

            <?php echo displayAlert(); ?>

            <div class="row g-4">
                <?php if (empty($departments)): ?>
                    <div class="col-12">
                        <div class="card-notion py-5 text-center animate-fade-up delay-100">
                            <div class="opacity-25 mb-3">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-building.svg" width="80">
                            </div>
                            <h4 class="text-dark fw-bold mb-2">No departments found</h4>
                            <p class="text-muted mb-0">Create your first department to get started.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($departments as $dept): ?>
                        <div class="col-xl-4 col-md-6">
                            <div class="card-notion h-100 animate-fade-up delay-100">
                                <div class="card-notion-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4 shadow-sm">
                                            <i class="bi bi-building fs-4"></i>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="view_department.php?id=<?= $dept['id'] ?>" class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" title="View Department">
                                                <i class="bi bi-eye text-primary"></i>
                                            </a>
                                            <button class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" onclick="editDepartment(<?= htmlspecialchars(json_encode($dept)) ?>)" title="Edit Department">
                                                <i class="bi bi-pencil text-warning"></i>
                                            </button>
                                            <button class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" onclick="deleteDepartment(<?= $dept['id'] ?>, '<?= sanitizeOutput($dept['department_name']) ?>')" title="Delete Department">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <h5 class="fw-bold text-dark mb-1"><?= sanitizeOutput($dept['department_name']) ?></h5>
                                    <p class="text-muted small mb-3"><?= sanitizeOutput($dept['department_code']) ?></p>
                                    
                                    <?php if (!empty($dept['description'])): ?>
                                        <p class="text-secondary small mb-3"><?= sanitizeOutput($dept['description']) ?></p>
                                    <?php endif; ?>

                                    <div class="d-flex gap-3 mt-3 pt-3 border-top">
                                        <div class="text-center flex-fill">
                                            <div class="fw-bold text-primary"><?= $dept['staff_count'] ?></div>
                                            <small class="text-muted">Staff</small>
                                        </div>
                                        <div class="text-center flex-fill">
                                            <div class="fw-bold text-success"><?= $dept['student_count'] ?></div>
                                            <small class="text-muted">Students</small>
                                        </div>
                                        <div class="text-center flex-fill">
                                            <div class="fw-bold text-info"><?= $dept['subject_count'] ?></div>
                                            <small class="text-muted">Subjects</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">Add New Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Department Name *</label>
                        <input type="text" name="department_name" class="form-control" required placeholder="e.g. Computer Science">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Department Code *</label>
                        <input type="text" name="department_code" class="form-control" required placeholder="e.g. CS">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Optional description..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Create Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="department_id" id="edit_department_id">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Department Name</label>
                        <input type="text" name="department_name" id="edit_department_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Department Code</label>
                        <input type="text" name="department_code" id="edit_department_code" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
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
<form method="POST" id="deleteDepartmentForm" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="department_id" id="delete_department_id">
</form>

<script>
    function editDepartment(dept) {
        document.getElementById('edit_department_id').value = dept.id;
        document.getElementById('edit_department_name').value = dept.department_name;
        document.getElementById('edit_department_code').value = dept.department_code;
        document.getElementById('edit_description').value = dept.description || '';

        new bootstrap.Modal(document.getElementById('editDepartmentModal')).show();
    }

    function deleteDepartment(id, name) {
        if (confirm('Delete department "' + name + '"?\n\nThis will unlink all associated staff, students, and subjects from this department.')) {
            document.getElementById('delete_department_id').value = id;
            document.getElementById('deleteDepartmentForm').submit();
        }
    }
</script>

<?php include '../includes/footer.php'; ?>

