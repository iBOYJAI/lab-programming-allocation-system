<?php

/**
 * Student Management - Premium Design
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

// --- Helper Functions ---

/**
 * Get student list with filtering and pagination
 */
function getStudentList($conn, $search = '', $department = 'all', $limit = 10, $offset = 0)
{
    $where = [];
    $params = [];

    if (!empty($search)) {
        // Check if roll_number column exists to avoid errors before migration
        $where[] = "(name LIKE ? OR roll_number LIKE ? OR email LIKE ?)";
        $term = "%$search%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    if ($department !== 'all') {
        $where[] = "department_id = ?";
        $params[] = $department;
    }

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Get Total Count
    $countSql = "SELECT COUNT(*) as total FROM students $whereClause";
    $stmt = $conn->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get Data with department join
    $sql = "SELECT s.*, d.department_name 
            FROM students s 
            LEFT JOIN departments d ON s.department_id = d.id 
            $whereClause 
            ORDER BY s.created_at DESC 
            LIMIT $limit OFFSET $offset";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return ['data' => $data, 'total' => $total];
}

// --- Request Handling ---

$items_per_page = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$items_per_page = in_array($items_per_page, [10, 25, 50, 100]) ? $items_per_page : 10;
$page = max(1, intval($_GET['page'] ?? 1));
$search = $_GET['search'] ?? '';
$filter_department = $_GET['department'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        $conn = getDbConnection();

        try {
            if ($action === 'add') {
                $name = trim($_POST['name']);
                $roll_number = trim($_POST['roll_number']);
                $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $gender = $_POST['gender'] ?? 'Male';

                if (empty($name) || empty($roll_number) || empty($password)) {
                    throw new Exception("Name, Roll Number, and Password are required.");
                }

                $hashed = hashPassword($password);

                $sql = "INSERT INTO students (name, roll_number, department_id, email, password, gender) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$name, $roll_number, $department_id, $email, $hashed, $gender]);

                logActivity($_SESSION['user_id'], 'admin', 'add_student', "Added student: $name ($roll_number)");
                $_SESSION['success'] = "Student added successfully!";
            } elseif ($action === 'edit') {
                $id = intval($_POST['student_id']);
                $name = trim($_POST['name']);
                $roll_number = trim($_POST['roll_number']);
                $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
                $email = trim($_POST['email']);
                $gender = $_POST['gender'];

                $sql = "UPDATE students SET name = ?, roll_number = ?, department_id = ?, email = ?, gender = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$name, $roll_number, $department_id, $email, $gender, $id]);

                logActivity($_SESSION['user_id'], 'admin', 'edit_student', "Updated student ID: $id");
                $_SESSION['success'] = "Student updated successfully.";
            } elseif ($action === 'manage_enrollment') {
                $student_id = intval($_POST['student_id']);
                $subject_ids = $_POST['subject_ids'] ?? [];

                // Clear existing active enrollments
                $conn->prepare("DELETE FROM student_subjects WHERE student_id = ?")->execute([$student_id]);

                if (!empty($subject_ids)) {
                    $stmt = $conn->prepare("INSERT INTO student_subjects (student_id, subject_id) VALUES (?, ?)");
                    foreach ($subject_ids as $sid) {
                        $stmt->execute([$student_id, intval($sid)]);
                    }
                }

                logActivity($_SESSION['user_id'], 'admin', 'manage_enrollment', "Updated enrollments for student ID: $student_id");
                $_SESSION['success'] = "Enrollments updated successfully!";
            } elseif ($action === 'delete') {
                $id = intval($_POST['student_id']);

                $sql = "DELETE FROM students WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$id]);

                logActivity($_SESSION['user_id'], 'admin', 'delete_student', "Deleted student ID: $id");
                $_SESSION['success'] = "Student deleted successfully.";
            } elseif ($action === 'bulk_delete') {
                $ids = $_POST['selected_ids'] ?? [];
                if (empty($ids)) {
                    throw new Exception("No students selected for deletion.");
                }

                $conn->beginTransaction();
                try {
                    $deleted_count = 0;
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $sql = "DELETE FROM students WHERE id IN ($placeholders)";
                    $stmt = $conn->prepare($sql);
                    if ($stmt->execute($ids)) {
                        $deleted_count = $stmt->rowCount();
                    }
                    $conn->commit();
                    logActivity($_SESSION['user_id'], 'admin', 'bulk_delete_students', "Bulk deleted $deleted_count students");
                    $_SESSION['success'] = "$deleted_count student(s) deleted successfully.";
                } catch (Exception $e) {
                    $conn->rollBack();
                    throw $e;
                }
            } elseif ($action === 'bulk_add') {
                $students = $_POST['students'] ?? [];
                $added = 0;
                $skipped = 0;

                foreach ($students as $s) {
                    $name = trim($s['name'] ?? '');
                    $roll = trim($s['roll'] ?? '');
                    $dept_id = !empty($s['department_id']) ? intval($s['department_id']) : null;
                    $email = trim($s['email'] ?? '');
                    $pwd = trim($s['password'] ?? '');
                    $gender = $s['gender'] ?? 'Male';

                    if (empty($name) || empty($roll) || empty($pwd)) {
                        $skipped++;
                        continue;
                    }

                    try {
                        $hashed = hashPassword($pwd);
                        $sql = "INSERT INTO students (name, roll_number, department_id, email, password, gender) VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        if ($stmt->execute([$name, $roll, $dept_id, $email, $hashed, $gender])) {
                            $added++;
                        } else {
                            $skipped++;
                        }
                    } catch (Exception $e) {
                        $skipped++;
                    }
                }

                $_SESSION['success'] = "$added students added successfully!";
                if ($skipped > 0) {
                    $_SESSION['warning'] = "$skipped entries were skipped due to missing data or duplicates.";
                }
                logActivity($_SESSION['user_id'], 'admin', 'bulk_add_students', "Added $added students via bulk form");
            } elseif ($action === 'import_csv') {
                if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Please upload a valid CSV file.");
                }

                $file = $_FILES['csv_file']['tmp_name'];
                $handle = fopen($file, "r");
                $header = fgetcsv($handle); // Assuming first line is header

                $imported = 0;
                $errors = 0;

                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) < 6) continue;

                    $name = trim($data[0]);
                    $roll_number = trim($data[1]);
                    $department_name = trim($data[2]);
                    $email = trim($data[3]);
                    $gender = trim($data[4]);
                    $password = trim($data[5]);

                    if (empty($name) || empty($roll_number) || empty($password)) {
                        $errors++;
                        continue;
                    }

                    // Try to find department_id from department name
                    $dept_id = null;
                    if (!empty($department_name)) {
                        $deptStmt = $conn->prepare("SELECT id FROM departments WHERE department_name = ? LIMIT 1");
                        $deptStmt->execute([$department_name]);
                        $deptRow = $deptStmt->fetch(PDO::FETCH_ASSOC);
                        if ($deptRow) {
                            $dept_id = $deptRow['id'];
                        }
                    }

                    $hashed = hashPassword($password);
                    $sql = "INSERT INTO students (name, roll_number, department_id, email, password, gender) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    try {
                        if ($stmt->execute([$name, $roll_number, $dept_id, $email, $hashed, $gender])) {
                            $imported++;
                        } else {
                            $errors++;
                        }
                    } catch (Exception $e) {
                        $errors++;
                    }
                }
                fclose($handle);

                $_SESSION['success'] = "Import complete! $imported students added." . ($errors > 0 ? " ($errors skipped due to errors or duplicates)" : "");
                logActivity($_SESSION['user_id'], 'admin', 'import_students', "Imported $imported students via CSV");
            }

            // Redirect to avoid resubmission
            $redirect_url = "manage_students.php?page=$page&search=" . urlencode($search) . "&department=$filter_department&limit=$items_per_page";
            header("Location: $redirect_url");
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }
}

// Fetch Data
$conn = getDbConnection();
$offset = ($page - 1) * $items_per_page;
$result = getStudentList($conn, $search, $filter_department, $items_per_page, $offset);
$students = $result['data'];
$total_records = $result['total'];
$total_pages = ceil($total_records / $items_per_page);

// Get departments for filter and dropdowns
try {
    $deptStmt = $conn->query("SELECT id, department_name, department_code FROM departments ORDER BY department_name");
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback if departments table doesn't exist yet
    $departments = [];
}

$page_title = 'Student Management';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
                <div>
                    <h1 class="fw-bold mb-1 text-dark fs-3">Student Directory</h1>
                    <p class="text-secondary mb-0">Manage student enrollment, allocations, and academic details.</p>
                </div>
                <div class="d-flex gap-2">
                    <button id="bulkDeleteBtn" class="btn btn-danger rounded-pill px-4 shadow-sm" disabled data-bs-toggle="modal" data-bs-target="#bulkDeleteModal" onclick="confirmBulkDelete()">
                        <i class="bi bi-trash me-2"></i>Delete Selected
                    </button>
                    <button class="btn btn-white border shadow-sm rounded-pill px-4 fw-medium" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-file-earmark-arrow-up me-2"></i>Import CSV
                    </button>
                    <button class="btn btn-indigo text-black border shadow-sm rounded-pill px-4 fw-medium" data-bs-toggle="modal" data-bs-target="#bulkAddStudentModal">
                        <i class="bi bi-person-lines-fill me-2"></i>Bulk Add
                    </button>
                    <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="bi bi-person-plus-fill me-2"></i>Add Student
                    </button>
                </div>
            </div>

            <?php echo displayAlert(); ?>

            <!-- Interactive Toolbar -->
            <div class="card-notion mb-4 animate-fade-up delay-100">
                <div class="card-notion-body p-3">
                    <form method="GET" class="row g-3 align-items-end" id="filterForm">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-uppercase text-muted">Search Student</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Name, Roll No, or Email..." value="<?= sanitizeOutput($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Department</label>
                            <select name="department" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?= $filter_department === 'all' ? 'selected' : '' ?>>All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>" <?= $filter_department == $dept['id'] ? 'selected' : '' ?>><?= sanitizeOutput($dept['department_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-uppercase text-muted">Show</label>
                            <select name="limit" class="form-select" onchange="this.form.submit()">
                                <option value="10" <?= $items_per_page == 10 ? 'selected' : '' ?>>10 rows</option>
                                <option value="25" <?= $items_per_page == 25 ? 'selected' : '' ?>>25 rows</option>
                                <option value="50" <?= $items_per_page == 50 ? 'selected' : '' ?>>50 rows</option>
                                <option value="100" <?= $items_per_page == 100 ? 'selected' : '' ?>>100 rows</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-secondary w-100">
                                <i class="bi bi-funnel me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Data Table -->
            <div class="card-notion animate-fade-up delay-200">
                <div class="card-notion-body p-0">
                    <?php if (empty($students)): ?>
                        <div class="text-center py-5">
                            <div class="opacity-25 mb-3">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-user-square.svg" width="60">
                            </div>
                            <h5 class="text-dark fw-bold mb-2">No students found</h5>
                            <p class="text-muted small">Try refreshing the page or clearing your filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 text-uppercase text-secondary" style="font-size: 0.75rem; width: 40px;">
                                            <input type="checkbox" id="selectAll" class="form-check-input" onchange="toggleSelectAll()">
                                        </th>
                                        <th class="text-uppercase text-secondary" style="font-size: 0.75rem;">Student Details</th>
                                        <th class="text-uppercase text-secondary" style="font-size: 0.75rem;">Department</th>
                                        <th class="text-uppercase text-secondary" style="font-size: 0.75rem;">Email</th>
                                        <th class="text-uppercase text-secondary" style="font-size: 0.75rem;">Joined</th>
                                        <th class="text-end pe-4 text-uppercase text-secondary" style="font-size: 0.75rem;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $s):
                                        $avatar_file = getProfileImage($s['gender'] ?? 'Male', $s['id'], $s['avatar_id'] ?? null);
                                    ?>
                                        <tr>
                                            <td class="ps-4">
                                                <input type="checkbox" class="form-check-input row-checkbox" value="<?= $s['id'] ?>" onchange="updateBulkDeleteButton()">
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <img src="../assets/images/Avatar/<?= $avatar_file ?>" alt="Avatar" class="rounded-circle shadow-sm" width="40" height="40">
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark text-truncate" style="max-width: 200px;"><?= sanitizeOutput($s['name']) ?></div>
                                                        <div class="d-flex align-items-center mt-1">
                                                            <span class="badge bg-light text-secondary border font-monospace me-2"><?= sanitizeOutput($s['roll_number'] ?? 'No ID') ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($s['department_name'])): ?>
                                                    <span class="badge bg-indigo-subtle text-indigo px-3 fw-medium">
                                                        <i class="bi bi-mortarboard me-1"></i><?= sanitizeOutput($s['department_name']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted small">Not Assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="text-truncate d-block small text-dark" style="max-width: 200px;"><i class="bi bi-envelope me-2 text-muted"></i><?= sanitizeOutput($s['email'] ?? 'N/A') ?></span>
                                            </td>
                                            <td class="text-muted small">
                                                <?= date('M d, Y', strtotime($s['created_at'])) ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <a href="view_student.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold" title="View Profile">
                                                        <i class="bi bi-eye text-primary me-1"></i>View
                                                    </a>
                                                    <button class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold" onclick="editStudent(<?= htmlspecialchars(json_encode($s)) ?>)" title="Edit Details">
                                                        <i class="bi bi-pencil text-warning me-1"></i>Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold" onclick='manageEnrollment(<?= $s['id'] ?>, "<?= sanitizeOutput($s['name']) ?>")' title="Manage Subjects">
                                                        <i class="bi bi-book text-success me-1"></i>Enroll
                                                    </button>
                                                    <button class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold text-danger" onclick="confirmDelete(<?= $s['id'] ?>, '<?= sanitizeOutput($s['name']) ?>')" title="Delete Student">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Footer Pagination -->
                        <div class="card-footer bg-white border-top py-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                                <div class="text-muted small">
                                    Showing <strong><?= $offset + 1 ?></strong> to <strong><?= min($offset + $items_per_page, $total_records) ?></strong> of <strong><?= $total_records ?></strong> students
                                </div>

                                <?php if ($total_pages > 1): ?>
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                                <a class="page-link border-0 text-dark" href="<?= ($page > 1) ? "?page=" . ($page - 1) . "&limit=$items_per_page&search=$search&department=$filter_department" : '#' ?>">
                                                    &laquo; Prev
                                                </a>
                                            </li>

                                            <?php
                                            $range = 2;
                                            $start = max(1, $page - $range);
                                            $end = min($total_pages, $page + $range);

                                            if ($start > 1) {
                                                echo '<li class="page-item"><a class="page-link border-0 text-dark" href="?page=1&limit=' . $items_per_page . '&search=' . $search . '&department=' . $filter_department . '">1</a></li>';
                                                if ($start > 2) echo '<li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>';
                                            }

                                            for ($i = $start; $i <= $end; $i++) {
                                                $active = ($i == $page) ? 'bg-primary text-white rounded' : 'text-dark';
                                                echo '<li class="page-item"><a class="page-link border-0 ' . $active . ' mx-1" href="?page=' . $i . '&limit=' . $items_per_page . '&search=' . $search . '&department=' . $filter_department . '">' . $i . '</a></li>';
                                            }

                                            if ($end < $total_pages) {
                                                if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>';
                                                echo '<li class="page-item"><a class="page-link border-0 text-dark" href="?page=' . $total_pages . '&limit=' . $items_per_page . '&search=' . $search . '&department=' . $filter_department . '">' . $total_pages . '</a></li>';
                                            }
                                            ?>

                                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                                <a class="page-link border-0 text-dark" href="<?= ($page < $total_pages) ? "?page=" . ($page + 1) . "&limit=$items_per_page&search=$search&department=$filter_department" : '#' ?>">
                                                    Next &raquo;
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Student -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Add New Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="add">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Alice Smith" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Roll Number <span class="text-danger">*</span></label>
                        <input type="text" name="roll_number" class="form-control" placeholder="e.g. 2024CS001" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">Select Department...</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= sanitizeOutput($dept['department_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="alice@example.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="Male" selected>Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Add Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Student -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Edit Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editStudentForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="student_id" id="edit_student_id">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Full Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Roll Number</label>
                        <input type="text" name="roll_number" id="edit_roll_number" class="form-control" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Department</label>
                            <select name="department_id" id="edit_department_id" class="form-select">
                                <option value="">Select Department...</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= sanitizeOutput($dept['department_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Gender</label>
                        <select name="gender" id="edit_gender" class="form-select">
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
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

<!-- Modal: Bulk Add Students -->
<div class="modal fade" id="bulkAddStudentModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" style="max-height: 90vh;">
            <div class="modal-header border-bottom px-4 py-3">
                <div>
                    <h5 class="modal-title fw-bold">Bulk Add Students</h5>
                    <p class="text-muted small mb-0">Quickly add multiple student records.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4 bg-light overflow-auto" style="max-height: calc(90vh - 120px);">
                    <input type="hidden" name="action" value="bulk_add">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="row mb-4 align-items-end g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Number of Students</label>
                            <input type="number" id="student_bulk_count" class="form-control" value="10" min="1" max="50">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Common Password</label>
                            <input type="text" id="common_student_password" class="form-control" value="123456" placeholder="Enter default password...">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-secondary w-100" type="button" onclick="generateStudentBulkRows()">
                                <i class="bi bi-arrow-repeat me-2"></i>Generate Rows
                            </button>
                        </div>
                    </div>

                    <div id="bulk-students-container" class="row g-3">
                        <!-- Student boxes will be generated here -->
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Save All Students</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Manage Enrollment -->
<div class="modal fade" id="enrollmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Manage Subject Enrollment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="manage_enrollment">
                    <input type="hidden" name="student_id" id="enroll_student_id">

                    <p class="text-secondary small mb-3">Enrolling <strong id="enroll_student_name" class="text-dark"></strong> in subjects allows them to participate in exams for those courses.</p>

                    <div id="subjectList" class="border rounded-3 p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Save Enrollments</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Import CSV -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Bulk Import Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="import_csv">

                    <div class="alert bg-blue-subtle text-blue border-0 small mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Please upload a CSV file with these columns:
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

<!-- Modal: Delete Confirmation -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center pt-5 pb-4 px-4">
                <div class="mb-3 text-danger">
                    <i class="bi bi-exclamation-circle display-1"></i>
                </div>
                <h5 class="fw-bold mb-2">Are you sure?</h5>
                <p class="text-muted mb-4 opacity-75">Do you really want to delete <strong id="delete_student_name" class="text-dark"></strong>? This cannot be undone.</p>

                <form method="POST" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="student_id" id="delete_student_id">

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger rounded-pill">Yes, Delete It</button>
                        <button type="button" class="btn btn-light text-muted rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Delete Modal -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center pt-5 pb-4 px-4">
                <div class="mb-3 text-danger">
                    <i class="bi bi-exclamation-circle display-1"></i>
                </div>
                <h5 class="fw-bold mb-2">Delete Selected Students?</h5>
                <p class="text-muted mb-4 opacity-75">Are you sure you want to delete <strong id="bulk_delete_count" class="text-dark">0</strong> selected student(s)? This cannot be undone.</p>

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

<script>
    function manageEnrollment(id, name) {
        document.getElementById('enroll_student_id').value = id;
        document.getElementById('enroll_student_name').textContent = name;

        const listContainer = document.getElementById('subjectList');
        listContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>';

        const modal = new bootstrap.Modal(document.getElementById('enrollmentModal'));
        modal.show();

        fetch(`get_student_subjects.php?student_id=${id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    let html = '';
                    data.subjects.forEach(sub => {
                        const checked = sub.enrolled ? 'checked' : '';
                        html += `
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="subject_ids[]" value="${sub.id}" id="sub_${sub.id}" ${checked}>
                                <label class="form-check-label d-flex justify-content-between w-100" for="sub_${sub.id}">
                                    <span>${sub.subject_name}</span>
                                    <small class="text-muted">${sub.subject_code}</small>
                                </label>
                            </div>
                        `;
                    });
                    listContainer.innerHTML = html || '<p class="text-center text-muted py-3">No subjects available.</p>';
                } else {
                    listContainer.innerHTML = `<p class="text-danger text-center py-3">${data.message}</p>`;
                }
            });
    }

    function editStudent(data) {
        document.getElementById('edit_student_id').value = data.id;
        document.getElementById('edit_name').value = data.name;
        document.getElementById('edit_roll_number').value = data.roll_number || '';
        document.getElementById('edit_department_id').value = data.department_id || '';
        document.getElementById('edit_email').value = data.email || '';
        document.getElementById('edit_gender').value = data.gender || 'Male';

        const modal = new bootstrap.Modal(document.getElementById('editStudentModal'));
        modal.show();
    }

    function confirmDelete(id, name) {
        document.getElementById('delete_student_id').value = id;
        document.getElementById('delete_student_name').textContent = name;

        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    const departmentsData = <?= json_encode($departments) ?>;
    
    function generateStudentBulkRows() {
        const container = document.getElementById('bulk-students-container');
        const count = parseInt(document.getElementById('student_bulk_count').value) || 10;
        const defaultPwd = document.getElementById('common_student_password').value || '123456';
        if (!container) return;

        container.innerHTML = '';
        for (let i = 0; i < count; i++) {
            const col = document.createElement('div');
            col.className = 'col-md-6 col-lg-4';
            
            let deptOptions = '<option value="">Select Department...</option>';
            departmentsData.forEach(dept => {
                deptOptions += `<option value="${dept.id}">${dept.department_name}</option>`;
            });
            
            col.innerHTML = `
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-white border text-secondary small">Student ${i + 1}</span>
                            <select name="students[${i}][gender]" class="form-select form-select-sm" style="width: 90px;">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <input type="text" name="students[${i}][name]" class="form-control form-control-sm mb-2 fw-bold" placeholder="Full Name *">
                        <input type="text" name="students[${i}][roll]" class="form-control form-control-sm mb-2" placeholder="Roll Number *">
                        <select name="students[${i}][department_id]" class="form-select form-select-sm mb-2">
                            ${deptOptions}
                        </select>
                        <input type="email" name="students[${i}][email]" class="form-control form-control-sm mb-2" placeholder="Email Address">
                        <input type="text" name="students[${i}][password]" class="form-control form-control-sm student-pwd-input" placeholder="Password *" value="${defaultPwd}">
                    </div>
                </div>
            `;
            container.appendChild(col);
        }
    }

    // Update all password fields when common password changes
    document.getElementById('common_student_password')?.addEventListener('input', function() {
        const pwd = this.value;
        const inputs = document.querySelectorAll('.student-pwd-input');
        inputs.forEach(input => input.value = pwd);
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

    // Auto-trigger edit modal if requested via URL
    document.addEventListener('DOMContentLoaded', function() {
        generateStudentBulkRows();
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action_trigger') === 'edit') {
            // Find the first student in the table and open edit
            <?php if (!empty($students)): ?>
                const firstStudent = <?= json_encode($students[0]) ?>;
                editStudent(firstStudent);
            <?php endif; ?>
        }
    });
</script>

<?php include '../includes/footer.php'; ?>