<?php

/**
 * Student Management - Staff Portal
 * Allows staff to manage students and their records
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
        redirect('manage_students.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name']);
        $roll_number = trim($_POST['roll_number']);
        $email = trim($_POST['email']);
        $dept = trim($_POST['department']);
        $gender = $_POST['gender'];
        $pass = $_POST['password'];

        if (empty($name) || empty($roll_number) || empty($pass)) {
            $_SESSION['error'] = 'Name, Roll No, and Password are required';
        } else {
            $hashed = hashPassword($pass);
            $sql = "INSERT INTO students (name, roll_number, email, department, gender, password_hash) VALUES (?, ?, ?, ?, ?, ?)";
            if (dbExecute($sql, [$name, $roll_number, $email, $dept, $gender, $hashed])) {
                logActivity($staff_id, 'staff', 'add_student', "Added student: $name ($roll_number)");
                $_SESSION['success'] = 'Student added successfully';
            } else {
                $_SESSION['error'] = 'Failed to add student';
            }
        }
        redirect('manage_students.php');
    } elseif ($action === 'edit') {
        $id = intval($_POST['student_id']);
        $name = trim($_POST['name']);
        $roll_number = trim($_POST['roll_number']);
        $email = trim($_POST['email']);
        $dept = trim($_POST['department']);
        $gender = $_POST['gender'];

        $sql = "UPDATE students SET name = ?, roll_number = ?, email = ?, department = ?, gender = ? WHERE id = ?";
        if (dbExecute($sql, [$name, $roll_number, $email, $dept, $gender, $id])) {
            logActivity($staff_id, 'staff', 'edit_student', "Updated student ID: $id");
            $_SESSION['success'] = 'Student updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update student';
        }
        redirect('manage_students.php');
        logActivity($staff_id, 'staff', 'delete_student', "Deleted student ID: $id");
        $_SESSION['success'] = 'Student deleted successfully';
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
                if (count($data) < 6) continue;
                $name = trim($data[0]);
                $roll_number = trim($data[1]);
                $dept = trim($data[2]);
                $email = trim($data[3]);
                $gender = trim($data[4]);
                $pass = trim($data[5]);

                if (empty($name) || empty($roll_number) || empty($pass)) {
                    $errors++;
                    continue;
                }

                $hashed = hashPassword($pass);
                $sql = "INSERT INTO students (name, roll_number, department, email, gender, password_hash) VALUES (?, ?, ?, ?, ?, ?)";
                try {
                    if (dbExecute($sql, [$name, $roll_number, $email, $dept, $gender, $hashed])) {
                        $imported++;
                    } else {
                        $errors++;
                    }
                } catch (Exception $e) {
                    $errors++;
                }
            }
            fclose($handle);
            $_SESSION['success'] = "Import complete! $imported students added." . ($errors > 0 ? " ($errors skipped)" : "");
            logActivity($staff_id, 'staff', 'import_students', "Imported $imported students via CSV");
        }
        redirect('manage_students.php');
    }
}

// Filters
$search = $_GET['search'] ?? '';
$dept_filter = $_GET['department'] ?? 'all';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR roll_number LIKE ? OR email LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($dept_filter !== 'all') {
    $where[] = "department = ?";
    $params[] = $dept_filter;
}

$where_sql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$students = dbQuery("SELECT * FROM students $where_sql ORDER BY name", $params);

// Get departments for filter
$departments = dbQuery("SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department");

$page_title = 'Student Directory';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
                <div>
                    <h1 class="fw-bold mb-1 text-dark fs-3">Student Directory</h1>
                    <p class="text-secondary mb-0">View and manage student profiles and information.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-white border shadow-sm rounded-pill px-4 fw-medium" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-file-earmark-arrow-up me-2"></i>Import CSV
                    </button>
                    <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-person-plus me-2"></i> Add Student
                    </button>
                </div>
            </div>

            <?php echo displayAlert(); ?>

            <!-- Filters -->
            <div class="card-notion mb-4 animate-fade-up delay-100">
                <div class="card-notion-body p-3">
                    <form class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Name, Roll No..." value="<?= sanitizeOutput($search) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Department</label>
                            <select name="department" class="form-select" onchange="this.form.submit()">
                                <option value="all">All Departments</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= sanitizeOutput($d['department']) ?>" <?= $dept_filter == $d['department'] ? 'selected' : '' ?>><?= sanitizeOutput($d['department']) ?></option>
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
                                    <th class="ps-4">Student</th>
                                    <th>Roll No</th>
                                    <th>Department</th>
                                    <th>Joined</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">No students found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $s): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="../assets/images/Avatar/<?= getProfileImage($s['gender'], $s['id'], $s['avatar_id']) ?>" class="rounded-circle shadow-sm" width="36" height="36">
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= sanitizeOutput($s['name']) ?></div>
                                                        <div class="small text-muted"><?= sanitizeOutput($s['email']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><code class="text-primary"><?= sanitizeOutput($s['roll_number']) ?></code></td>
                                            <td><span class="badge bg-light text-dark border"><?= sanitizeOutput($s['department'] ?: 'N/A') ?></span></td>
                                            <td class="small text-muted"><?= formatDate($s['created_at'], 'M d, Y') ?></td>
                                            <td class="text-end pe-4">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-icon btn-ghost-secondary rounded-circle" data-bs-toggle="dropdown">
                                                        <i class="bi bi-three-dots"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="view_student.php?id=<?= $s['id'] ?>"><i class="bi bi-eye me-2 text-primary"></i>View Profile</a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="editStudent(<?= htmlspecialchars(json_encode($s)) ?>)"><i class="bi bi-pencil me-2 text-warning"></i>Edit</a></li>
                                                        <li>
                                                            <hr class="dropdown-divider">
                                                        </li>
                                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteStudent(<?= $s['id'] ?>, '<?= sanitizeOutput($s['name']) ?>')"><i class="bi bi-trash me-2"></i>Delete</a></li>
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

<!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">Bulk Import Students</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="import_csv">

                    <div class="alert bg-blue-subtle text-blue border-0 small mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Please upload a CSV file with:
                        <code class="d-block mt-1">name, roll_number, department, email, gender, password</code>
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

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">New Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Roll Number</label>
                        <input type="text" name="roll_number" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Department</label>
                            <input type="text" name="department" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Create Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="student_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Roll Number</label>
                        <input type="text" name="roll_number" id="edit_roll_number" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Department</label>
                            <input type="text" name="department" id="edit_dept" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Gender</label>
                            <select name="gender" id="edit_gender" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
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
    <input type="hidden" name="student_id" id="delete_id">
</form>

<script>
    function editStudent(s) {
        document.getElementById('edit_id').value = s.id;
        document.getElementById('edit_name').value = s.name;
        document.getElementById('edit_roll_number').value = s.roll_number || '';
        document.getElementById('edit_dept').value = s.department || '';
        document.getElementById('edit_gender').value = s.gender || 'Male';
        document.getElementById('edit_email').value = s.email || '';
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }

    function deleteStudent(id, name) {
        if (confirm(`Delete student: ${name}?`)) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteForm').submit();
        }
    }
</script>

<?php include '../includes/footer.php'; ?>