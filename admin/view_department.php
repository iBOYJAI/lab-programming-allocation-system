<?php

/**
 * View Department Details - Premium Design with Full CRUD Operations
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

if (!isset($_GET['id'])) {
    header("Location: manage_departments.php");
    exit;
}

$department_id = intval($_GET['id']);
$conn = getDbConnection();

// Fetch Department Details
$stmt = $conn->prepare("SELECT * FROM departments WHERE id = ?");
$stmt->execute([$department_id]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$department) {
    $_SESSION['error'] = "Department not found.";
    header("Location: manage_departments.php");
    exit;
}

// Handle POST requests for CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        $entity_type = $_POST['entity_type'] ?? '';
        
        try {
            // STUDENT OPERATIONS
            if ($entity_type === 'student') {
                if ($action === 'add') {
                    $name = trim($_POST['name']);
                    $roll_number = trim($_POST['roll_number']);
                    $email = trim($_POST['email'] ?? '');
                    $password = $_POST['password'];
                    $gender = $_POST['gender'] ?? 'Male';
                    $semester = !empty($_POST['semester']) ? intval($_POST['semester']) : null;
                    
                    if (empty($name) || empty($roll_number) || empty($password)) {
                        throw new Exception("Name, Roll Number, and Password are required.");
                    }
                    
                    $hashed = hashPassword($password);
                    $sql = "INSERT INTO students (name, roll_number, department_id, email, password, gender, semester) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$name, $roll_number, $department_id, $email, $hashed, $gender, $semester]);
                    
                    logActivity($_SESSION['user_id'], 'admin', 'add_student', "Added student: $name ($roll_number)");
                    $_SESSION['success'] = "Student added successfully!";
                } elseif ($action === 'edit') {
                    $id = intval($_POST['id']);
                    $name = trim($_POST['name']);
                    $roll_number = trim($_POST['roll_number']);
                    $email = trim($_POST['email'] ?? '');
                    $gender = $_POST['gender'];
                    $semester = !empty($_POST['semester']) ? intval($_POST['semester']) : null;
                    
                    $sql = "UPDATE students SET name = ?, roll_number = ?, email = ?, gender = ?, semester = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$name, $roll_number, $email, $gender, $semester, $id]);
                    
                    logActivity($_SESSION['user_id'], 'admin', 'edit_student', "Updated student ID: $id");
                    $_SESSION['success'] = "Student updated successfully.";
                } elseif ($action === 'delete') {
                    $id = intval($_POST['id']);
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
                        $placeholders = implode(',', array_fill(0, count($ids), '?'));
                        $sql = "DELETE FROM students WHERE id IN ($placeholders)";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute($ids);
                        $deleted_count = $stmt->rowCount();
                        $conn->commit();
                        
                        logActivity($_SESSION['user_id'], 'admin', 'bulk_delete_students', "Bulk deleted $deleted_count students");
                        $_SESSION['success'] = "$deleted_count student(s) deleted successfully.";
                    } catch (Exception $e) {
                        $conn->rollBack();
                        throw $e;
                    }
                }
            }
            // STAFF OPERATIONS
            elseif ($entity_type === 'staff') {
                if ($action === 'add') {
                    $name = trim($_POST['name']);
                    $email = trim($_POST['email'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $password = $_POST['password'];
                    $gender = $_POST['gender'] ?? 'Male';
                    $staff_status = $_POST['staff_status'] ?? 'Active';
                    
                    if (empty($name) || empty($password)) {
                        throw new Exception("Name and password are required.");
                    }
                    
                    $staff_id = generateStaffId();
                    $hashed = hashPassword($password);
                    $sql = "INSERT INTO staff (staff_id, name, email, phone, password, status, gender, department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$staff_id, $name, $email, $phone, $hashed, $staff_status, $gender, $department_id]);
                    
                    logActivity($_SESSION['user_id'], 'admin', 'add_staff', "Added staff: $name ($staff_id)");
                    $_SESSION['success'] = "Staff added successfully! ID: $staff_id";
                } elseif ($action === 'edit') {
                    $id = intval($_POST['id']);
                    $name = trim($_POST['name']);
                    $email = trim($_POST['email'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $gender = $_POST['gender'];
                    $staff_status = $_POST['staff_status'];
                    
                    $sql = "UPDATE staff SET name = ?, email = ?, phone = ?, status = ?, gender = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$name, $email, $phone, $staff_status, $gender, $id]);
                    
                    logActivity($_SESSION['user_id'], 'admin', 'edit_staff', "Updated staff ID: $id");
                    $_SESSION['success'] = "Staff updated successfully.";
                } elseif ($action === 'delete') {
                    $id = intval($_POST['id']);
                    $sql = "DELETE FROM staff WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$id]);
                    
                    logActivity($_SESSION['user_id'], 'admin', 'delete_staff', "Deleted staff ID: $id");
                    $_SESSION['success'] = "Staff deleted successfully.";
                } elseif ($action === 'bulk_delete') {
                    $ids = $_POST['selected_ids'] ?? [];
                    if (empty($ids)) {
                        throw new Exception("No staff members selected for deletion.");
                    }
                    
                    $conn->beginTransaction();
                    try {
                        $placeholders = implode(',', array_fill(0, count($ids), '?'));
                        $sql = "DELETE FROM staff WHERE id IN ($placeholders)";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute($ids);
                        $deleted_count = $stmt->rowCount();
                        $conn->commit();
                        
                        logActivity($_SESSION['user_id'], 'admin', 'bulk_delete_staff', "Bulk deleted $deleted_count staff members");
                        $_SESSION['success'] = "$deleted_count staff member(s) deleted successfully.";
                    } catch (Exception $e) {
                        $conn->rollBack();
                        throw $e;
                    }
                }
            }
            // SUBJECT OPERATIONS
            elseif ($entity_type === 'subject') {
                if ($action === 'add') {
                    $name = trim($_POST['subject_name']);
                    $code = trim($_POST['subject_code']);
                    $lang = $_POST['language'];
                    $lab_id = !empty($_POST['lab_id']) ? intval($_POST['lab_id']) : null;
                    
                    if (empty($name) || empty($code)) {
                        throw new Exception("Subject name and code are required.");
                    }
                    
                    $sql = "INSERT INTO subjects (subject_name, subject_code, language, lab_id, department_id) VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$name, $code, $lang, $lab_id, $department_id]);
                    
                    logActivity($_SESSION['user_id'], 'admin', 'add_subject', "Added subject: $name");
                    $_SESSION['success'] = 'Subject added successfully';
                } elseif ($action === 'edit') {
                    $id = intval($_POST['id']);
                    $name = trim($_POST['subject_name']);
                    $code = trim($_POST['subject_code']);
                    $lang = $_POST['language'];
                    $lab_id = !empty($_POST['lab_id']) ? intval($_POST['lab_id']) : null;
                    
                    $sql = "UPDATE subjects SET subject_name = ?, subject_code = ?, language = ?, lab_id = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$name, $code, $lang, $lab_id, $id]);
                    
                    logActivity($_SESSION['user_id'], 'admin', 'edit_subject', "Updated subject: $name");
                    $_SESSION['success'] = 'Subject updated successfully';
                } elseif ($action === 'delete') {
                    $id = intval($_POST['id']);
                    $sql = "DELETE FROM subjects WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$id]);
                    
                    logActivity($_SESSION['user_id'], 'admin', 'delete_subject', "Deleted subject ID: $id");
                    $_SESSION['success'] = 'Subject deleted successfully';
                } elseif ($action === 'bulk_delete') {
                    $ids = $_POST['selected_ids'] ?? [];
                    if (empty($ids)) {
                        throw new Exception("No subjects selected for deletion.");
                    }
                    
                    $conn->beginTransaction();
                    try {
                        $placeholders = implode(',', array_fill(0, count($ids), '?'));
                        $sql = "DELETE FROM subjects WHERE id IN ($placeholders)";
                        $stmt = $conn->prepare($sql);
                        $stmt->execute($ids);
                        $deleted_count = $stmt->rowCount();
                        $conn->commit();
                        
                        logActivity($_SESSION['user_id'], 'admin', 'bulk_delete_subjects', "Bulk deleted $deleted_count subjects");
                        $_SESSION['success'] = "$deleted_count subject(s) deleted successfully.";
                    } catch (Exception $e) {
                        $conn->rollBack();
                        throw $e;
                    }
                }
            }
            
            // Redirect to avoid resubmission
            header("Location: view_department.php?id=$department_id");
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }
}

// Fetch counts
$staff_count = dbQuery("SELECT COUNT(*) as total FROM staff WHERE department_id = ?", [$department_id])[0]['total'];
$student_count = dbQuery("SELECT COUNT(*) as total FROM students WHERE department_id = ?", [$department_id])[0]['total'];
$subject_count = dbQuery("SELECT COUNT(*) as total FROM subjects WHERE department_id = ?", [$department_id])[0]['total'];
$exam_count = dbQuery("
    SELECT COUNT(*) as total 
    FROM exam_sessions es
    JOIN subjects s ON es.subject_id = s.id
    WHERE s.department_id = ?
", [$department_id])[0]['total'];

// Fetch data for tabs
$students = dbQuery("SELECT * FROM students WHERE department_id = ? ORDER BY name ASC", [$department_id]);
$staff_list = dbQuery("SELECT * FROM staff WHERE department_id = ? ORDER BY name ASC", [$department_id]);
$subjects = dbQuery("
    SELECT s.*, l.lab_name 
    FROM subjects s
    LEFT JOIN labs l ON s.lab_id = l.id
    WHERE s.department_id = ?
    ORDER BY s.subject_name ASC
", [$department_id]);
$exams = dbQuery("
    SELECT es.*, s.subject_name, s.subject_code, st.name as staff_name, st.staff_id, l.lab_name
    FROM exam_sessions es
    JOIN subjects s ON es.subject_id = s.id
    JOIN staff st ON es.staff_id = st.id
    JOIN labs l ON es.lab_id = l.id
    WHERE s.department_id = ?
    ORDER BY es.started_at DESC
", [$department_id]);

// Fetch dropdowns
$labs = dbQuery("SELECT id, lab_name FROM labs ORDER BY lab_name");

$page_title = 'Department Details';
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
                        <a href="manage_departments.php" class="btn btn-white border shadow-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="page-title mb-0 text-dark"><?= sanitizeOutput($department['department_name']) ?></h1>
                            <p class="text-muted small mb-0 fw-bold font-monospace">Department Code: <?= sanitizeOutput($department['department_code']) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold transition-all" onclick="editDepartment(<?= htmlspecialchars(json_encode($department)) ?>)">
                        <i class="bi bi-pencil me-2"></i>Edit Department
                    </button>
                </div>
            </div>

            <?php echo displayAlert(); ?>

            <?php if (!empty($department['description'])): ?>
                <div class="card card-notion border-0 shadow-sm mb-4">
                    <div class="card-notion-body p-4">
                        <h6 class="fw-bold mb-2 text-dark small text-uppercase">Description</h6>
                        <p class="text-secondary mb-0"><?= sanitizeOutput($department['description']) ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Operational Metrics -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card card-notion border-0 shadow-sm h-100">
                        <div class="card-notion-body p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="small text-uppercase fw-900 text-secondary" style="letter-spacing: 0.1em;">Staff Members</span>
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-people small"></i>
                                </div>
                            </div>
                            <h2 class="fw-900 mb-0 text-dark"><?= $staff_count ?></h2>
                            <p class="text-muted x-small mt-2 mb-0">Total Staff</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-notion border-0 shadow-sm h-100">
                        <div class="card-notion-body p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="small text-uppercase fw-900 text-secondary" style="letter-spacing: 0.1em;">Students</span>
                                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person-check small"></i>
                                </div>
                            </div>
                            <h2 class="fw-900 mb-0 text-dark"><?= $student_count ?></h2>
                            <p class="text-muted x-small mt-2 mb-0">Total Students</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-notion border-0 shadow-sm h-100">
                        <div class="card-notion-body p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="small text-uppercase fw-900 text-secondary" style="letter-spacing: 0.1em;">Subjects</span>
                                <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-book small"></i>
                                </div>
                            </div>
                            <h2 class="fw-900 mb-0 text-dark"><?= $subject_count ?></h2>
                            <p class="text-muted x-small mt-2 mb-0">Total Subjects</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-notion border-0 shadow-sm h-100">
                        <div class="card-notion-body p-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <span class="small text-uppercase fw-900 text-secondary" style="letter-spacing: 0.1em;">Exam Sessions</span>
                                <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-clipboard-check small"></i>
                                </div>
                            </div>
                            <h2 class="fw-900 mb-0 text-dark"><?= $exam_count ?></h2>
                            <p class="text-muted x-small mt-2 mb-0">Total Exams</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Multi-Threaded Resource Monitoring -->
            <div class="card card-notion border-0 shadow-sm h-100 d-flex flex-column">
                <div class="card-header bg-white border-bottom p-0">
                    <ul class="nav nav-tabs border-bottom-0 px-4" role="tablist">
                        <li class="nav-item">
                            <button class="nav-tab-notion nav-link active py-3 px-4 fw-bold text-uppercase border-0 h-100" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab" aria-controls="students" aria-selected="true" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="bi bi-people me-2"></i>Students
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-tab-notion nav-link py-3 px-4 fw-bold text-uppercase border-0 h-100" data-bs-toggle="tab" data-bs-target="#staff" type="button" role="tab" aria-controls="staff" aria-selected="false" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="bi bi-person-badge me-2"></i>Staff
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-tab-notion nav-link py-3 px-4 fw-bold text-uppercase border-0 h-100" data-bs-toggle="tab" data-bs-target="#subjects" type="button" role="tab" aria-controls="subjects" aria-selected="false" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="bi bi-book me-2"></i>Subjects
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-tab-notion nav-link py-3 px-4 fw-bold text-uppercase border-0 h-100" data-bs-toggle="tab" data-bs-target="#exams" type="button" role="tab" aria-controls="exams" aria-selected="false" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                                <i class="bi bi-clipboard-check me-2"></i>Exams
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-notion-body p-0 flex-grow-1">
                    <div class="tab-content h-100">
                        <!-- Students Tab -->
                        <div class="tab-pane fade show active h-100" id="students" role="tabpanel">
                            <div class="p-4 border-bottom bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">Students List</h6>
                                    <div class="d-flex gap-2">
                                        <button id="bulkDeleteStudentsBtn" class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm" disabled onclick="confirmBulkDelete('student')">
                                            <i class="bi bi-trash me-1"></i>Bulk Delete
                                        </button>
                                        <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                            <i class="bi bi-plus-circle me-1"></i>Add Student
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table notion-table table-hover align-middle mb-0">
                                    <thead class="bg-light bg-opacity-50">
                                        <tr>
                                            <th class="ps-4 py-3" style="width: 40px;">
                                                <input type="checkbox" class="form-check-input" id="selectAllStudents" onchange="toggleSelectAll('student')">
                                            </th>
                                            <th class="small text-uppercase text-secondary fw-bold">Student Name</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Roll Number</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Email</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Semester</th>
                                            <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($students)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <div class="text-muted opacity-50 mb-2"><i class="bi bi-person-exclamation fs-1"></i></div>
                                                    <div class="text-muted small fw-medium">No students found in this department.</div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($students as $stu): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <input type="checkbox" class="form-check-input row-checkbox-student" value="<?= $stu['id'] ?>" onchange="updateBulkDeleteButton('student')">
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="../assets/images/Avatar/<?= getProfileImage($stu['gender'] ?? 'Male', $stu['id'], $stu['avatar_id'] ?? null) ?>" class="rounded-circle" width="32" height="32">
                                                            <div class="fw-bold text-dark"><?= sanitizeOutput($stu['name']) ?></div>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge bg-white border text-dark font-monospace x-small"><?= sanitizeOutput($stu['roll_number']) ?></span></td>
                                                    <td class="text-muted small"><?= sanitizeOutput($stu['email'] ?? 'N/A') ?></td>
                                                    <td><span class="badge bg-light text-secondary border rounded-pill px-3 x-small fw-bold"><?= $stu['semester'] ?? 'N/A' ?></span></td>
                                                    <td class="pe-4 text-end">
                                                        <div class="d-flex justify-content-end gap-1">
                                                            <a href="view_student.php?id=<?= $stu['id'] ?>" class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" title="View">
                                                                <i class="bi bi-eye text-primary"></i>
                                                            </a>
                                                            <button class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" onclick="editStudent(<?= htmlspecialchars(json_encode($stu)) ?>)" title="Edit">
                                                                <i class="bi bi-pencil text-warning"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" onclick="confirmDelete('student', <?= $stu['id'] ?>, '<?= sanitizeOutput($stu['name']) ?>')" title="Delete">
                                                                <i class="bi bi-trash text-danger"></i>
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

                        <!-- Staff Tab -->
                        <div class="tab-pane fade h-100" id="staff" role="tabpanel">
                            <div class="p-4 border-bottom bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">Staff List</h6>
                                    <div class="d-flex gap-2">
                                        <button id="bulkDeleteStaffBtn" class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm" disabled onclick="confirmBulkDelete('staff')">
                                            <i class="bi bi-trash me-1"></i>Bulk Delete
                                        </button>
                                        <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                                            <i class="bi bi-plus-circle me-1"></i>Add Staff
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table notion-table table-hover align-middle mb-0">
                                    <thead class="bg-light bg-opacity-50">
                                        <tr>
                                            <th class="ps-4 py-3" style="width: 40px;">
                                                <input type="checkbox" class="form-check-input" id="selectAllStaff" onchange="toggleSelectAll('staff')">
                                            </th>
                                            <th class="small text-uppercase text-secondary fw-bold">Staff Name</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Staff ID</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Email</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Status</th>
                                            <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($staff_list)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <div class="text-muted opacity-50 mb-2"><i class="bi bi-people fs-1"></i></div>
                                                    <div class="text-muted small fw-medium">No staff members found in this department.</div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($staff_list as $st): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <input type="checkbox" class="form-check-input row-checkbox-staff" value="<?= $st['id'] ?>" onchange="updateBulkDeleteButton('staff')">
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="../assets/images/Avatar/<?= getProfileImage($st['gender'] ?? 'Male', $st['id'], $st['avatar_id'] ?? null) ?>" class="rounded-circle" width="32" height="32">
                                                            <div class="fw-bold text-dark"><?= sanitizeOutput($st['name']) ?></div>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge bg-white border text-dark font-monospace x-small"><?= sanitizeOutput($st['staff_id']) ?></span></td>
                                                    <td class="text-muted small"><?= sanitizeOutput($st['email'] ?? 'N/A') ?></td>
                                                    <td>
                                                        <span class="badge rounded-pill <?= ($st['status'] ?? 'Active') === 'Active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?> px-3 fw-medium">
                                                            <?= $st['status'] ?? 'Active' ?>
                                                        </span>
                                                    </td>
                                                    <td class="pe-4 text-end">
                                                        <div class="d-flex justify-content-end gap-1">
                                                            <a href="view_staff.php?id=<?= $st['id'] ?>" class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" title="View">
                                                                <i class="bi bi-eye text-primary"></i>
                                                            </a>
                                                            <button class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" onclick="editStaff(<?= htmlspecialchars(json_encode($st)) ?>)" title="Edit">
                                                                <i class="bi bi-pencil text-warning"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" onclick="confirmDelete('staff', <?= $st['id'] ?>, '<?= sanitizeOutput($st['name']) ?>')" title="Delete">
                                                                <i class="bi bi-trash text-danger"></i>
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

                        <!-- Subjects Tab -->
                        <div class="tab-pane fade h-100" id="subjects" role="tabpanel">
                            <div class="p-4 border-bottom bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">Subjects List</h6>
                                    <div class="d-flex gap-2">
                                        <button id="bulkDeleteSubjectsBtn" class="btn btn-sm btn-danger rounded-pill px-3 shadow-sm" disabled onclick="confirmBulkDelete('subject')">
                                            <i class="bi bi-trash me-1"></i>Bulk Delete
                                        </button>
                                        <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                                            <i class="bi bi-plus-circle me-1"></i>Add Subject
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table notion-table table-hover align-middle mb-0">
                                    <thead class="bg-light bg-opacity-50">
                                        <tr>
                                            <th class="ps-4 py-3" style="width: 40px;">
                                                <input type="checkbox" class="form-check-input" id="selectAllSubjects" onchange="toggleSelectAll('subject')">
                                            </th>
                                            <th class="small text-uppercase text-secondary fw-bold">Subject Name</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Subject Code</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Language</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Lab</th>
                                            <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($subjects)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5">
                                                    <div class="text-muted opacity-50 mb-2"><i class="bi bi-book fs-1"></i></div>
                                                    <div class="text-muted small fw-medium">No subjects found in this department.</div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($subjects as $sub): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <input type="checkbox" class="form-check-input row-checkbox-subject" value="<?= $sub['id'] ?>" onchange="updateBulkDeleteButton('subject')">
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-dark"><?= sanitizeOutput($sub['subject_name']) ?></div>
                                                    </td>
                                                    <td><span class="badge bg-white border text-dark font-monospace x-small"><?= sanitizeOutput($sub['subject_code']) ?></span></td>
                                                    <td><span class="badge bg-light text-secondary border rounded-pill px-3 x-small fw-bold"><?= sanitizeOutput($sub['language']) ?></span></td>
                                                    <td class="text-muted small"><?= sanitizeOutput($sub['lab_name'] ?? 'N/A') ?></td>
                                                    <td class="pe-4 text-end">
                                                        <div class="d-flex justify-content-end gap-1">
                                                            <a href="view_subject.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" title="View">
                                                                <i class="bi bi-eye text-primary"></i>
                                                            </a>
                                                            <button class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" onclick="editSubject(<?= htmlspecialchars(json_encode($sub)) ?>)" title="Edit">
                                                                <i class="bi bi-pencil text-warning"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" onclick="confirmDelete('subject', <?= $sub['id'] ?>, '<?= sanitizeOutput($sub['subject_name']) ?>')" title="Delete">
                                                                <i class="bi bi-trash text-danger"></i>
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

                        <!-- Exams Tab -->
                        <div class="tab-pane fade h-100" id="exams" role="tabpanel">
                            <div class="p-4 border-bottom bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">Exam Sessions</h6>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table notion-table table-hover align-middle mb-0">
                                    <thead class="bg-light bg-opacity-50">
                                        <tr>
                                            <th class="ps-4 py-3 small text-uppercase text-secondary fw-bold">Session Code</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Subject</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Staff</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Test Type</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Status</th>
                                            <th class="small text-uppercase text-secondary fw-bold">Started</th>
                                            <th class="pe-4 text-end small text-uppercase text-secondary fw-bold">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($exams)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <div class="text-muted opacity-50 mb-2"><i class="bi bi-clipboard-check fs-1"></i></div>
                                                    <div class="text-muted small fw-medium">No exam sessions found for this department.</div>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($exams as $exam): ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <span class="badge bg-white border text-dark font-monospace fw-bold"><?= sanitizeOutput($exam['session_code']) ?></span>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-dark small"><?= sanitizeOutput($exam['subject_name']) ?></div>
                                                        <div class="text-muted x-small"><?= sanitizeOutput($exam['subject_code']) ?></div>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-dark small"><?= sanitizeOutput($exam['staff_name']) ?></div>
                                                        <div class="text-muted x-small font-monospace"><?= sanitizeOutput($exam['staff_id']) ?></div>
                                                    </td>
                                                    <td><span class="badge bg-light text-secondary border rounded-pill px-3 x-small fw-bold"><?= sanitizeOutput($exam['test_type']) ?></span></td>
                                                    <td>
                                                        <span class="badge rounded-pill <?= $exam['status'] === 'Active' ? 'bg-success-subtle text-success' : ($exam['status'] === 'Completed' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary') ?> px-3 fw-medium">
                                                            <?= sanitizeOutput($exam['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-muted small font-monospace"><?= date('M j, Y H:i', strtotime($exam['started_at'])) ?></td>
                                                    <td class="pe-4 text-end">
                                                        <a href="view_session_details.php?id=<?= $exam['id'] ?>" class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light transition-all">
                                                            <i class="bi bi-eye text-primary me-1"></i>View
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

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="manage_departments.php">
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

<!-- Add Student Modal -->
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
                    <input type="hidden" name="entity_type" value="student">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Roll Number <span class="text-danger">*</span></label>
                        <input type="text" name="roll_number" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Semester</label>
                            <input type="number" name="semester" class="form-control" min="1" max="8">
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

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Edit Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="entity_type" value="student">
                    <input type="hidden" name="id" id="edit_student_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Full Name</label>
                        <input type="text" name="name" id="edit_student_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Roll Number</label>
                        <input type="text" name="roll_number" id="edit_student_roll" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Email</label>
                            <input type="email" name="email" id="edit_student_email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Semester</label>
                            <input type="number" name="semester" id="edit_student_semester" class="form-control" min="1" max="8">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Gender</label>
                        <select name="gender" id="edit_student_gender" class="form-select">
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

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Add New Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="entity_type" value="staff">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="Male" selected>Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Status</label>
                            <select name="staff_status" class="form-select">
                                <option value="Active" selected>Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Add Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Edit Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="entity_type" value="staff">
                    <input type="hidden" name="id" id="edit_staff_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Full Name</label>
                        <input type="text" name="name" id="edit_staff_name" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Email</label>
                            <input type="email" name="email" id="edit_staff_email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Phone</label>
                            <input type="text" name="phone" id="edit_staff_phone" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Gender</label>
                            <select name="gender" id="edit_staff_gender" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Status</label>
                            <select name="staff_status" id="edit_staff_status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
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

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Add New Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="entity_type" value="subject">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Subject Name <span class="text-danger">*</span></label>
                        <input type="text" name="subject_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Subject Code <span class="text-danger">*</span></label>
                        <input type="text" name="subject_code" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Language</label>
                            <select name="language" class="form-select">
                                <option value="C">C</option>
                                <option value="C++">C++</option>
                                <option value="Java">Java</option>
                                <option value="Python">Python</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Lab</label>
                            <select name="lab_id" class="form-select">
                                <option value="">Select Lab...</option>
                                <?php foreach ($labs as $lab): ?>
                                    <option value="<?= $lab['id'] ?>"><?= sanitizeOutput($lab['lab_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal fade" id="editSubjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Edit Subject</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="entity_type" value="subject">
                    <input type="hidden" name="id" id="edit_subject_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Subject Name</label>
                        <input type="text" name="subject_name" id="edit_subject_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Subject Code</label>
                        <input type="text" name="subject_code" id="edit_subject_code" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Language</label>
                            <select name="language" id="edit_subject_language" class="form-select">
                                <option value="C">C</option>
                                <option value="C++">C++</option>
                                <option value="Java">Java</option>
                                <option value="Python">Python</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Lab</label>
                            <select name="lab_id" id="edit_subject_lab" class="form-select">
                                <option value="">Select Lab...</option>
                                <?php foreach ($labs as $lab): ?>
                                    <option value="<?= $lab['id'] ?>"><?= sanitizeOutput($lab['lab_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center pt-5 pb-4 px-4">
                <div class="mb-3 text-danger">
                    <i class="bi bi-exclamation-circle display-1"></i>
                </div>
                <h5 class="fw-bold mb-2">Are you sure?</h5>
                <p class="text-muted mb-4 opacity-75">Do you really want to delete <strong id="delete_name" class="text-dark"></strong>? This cannot be undone.</p>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="entity_type" id="delete_entity_type">
                    <input type="hidden" name="id" id="delete_id">
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
                <h5 class="fw-bold mb-2">Delete Selected Items?</h5>
                <p class="text-muted mb-4 opacity-75">Are you sure you want to delete <strong id="bulk_delete_count" class="text-dark">0</strong> selected item(s)? This cannot be undone.</p>
                <form method="POST" id="bulkDeleteForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="bulk_delete">
                    <input type="hidden" name="entity_type" id="bulk_delete_entity_type">
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
    function editDepartment(dept) {
        document.getElementById('edit_department_id').value = dept.id;
        document.getElementById('edit_department_name').value = dept.department_name;
        document.getElementById('edit_department_code').value = dept.department_code;
        document.getElementById('edit_description').value = dept.description || '';
        new bootstrap.Modal(document.getElementById('editDepartmentModal')).show();
    }

    function editStudent(data) {
        document.getElementById('edit_student_id').value = data.id;
        document.getElementById('edit_student_name').value = data.name;
        document.getElementById('edit_student_roll').value = data.roll_number;
        document.getElementById('edit_student_email').value = data.email || '';
        document.getElementById('edit_student_semester').value = data.semester || '';
        document.getElementById('edit_student_gender').value = data.gender || 'Male';
        new bootstrap.Modal(document.getElementById('editStudentModal')).show();
    }

    function editStaff(data) {
        document.getElementById('edit_staff_id').value = data.id;
        document.getElementById('edit_staff_name').value = data.name;
        document.getElementById('edit_staff_email').value = data.email || '';
        document.getElementById('edit_staff_phone').value = data.phone || '';
        document.getElementById('edit_staff_gender').value = data.gender || 'Male';
        document.getElementById('edit_staff_status').value = data.status || 'Active';
        new bootstrap.Modal(document.getElementById('editStaffModal')).show();
    }

    function editSubject(data) {
        document.getElementById('edit_subject_id').value = data.id;
        document.getElementById('edit_subject_name').value = data.subject_name;
        document.getElementById('edit_subject_code').value = data.subject_code;
        document.getElementById('edit_subject_language').value = data.language || 'C';
        document.getElementById('edit_subject_lab').value = data.lab_id || '';
        new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
    }

    function confirmDelete(entityType, id, name) {
        document.getElementById('delete_id').value = id;
        document.getElementById('delete_entity_type').value = entityType;
        document.getElementById('delete_name').textContent = name;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }

    function toggleSelectAll(entityType) {
        // Map entity types to their checkbox ID format
        const idMap = {
            'student': 'selectAllStudents',
            'staff': 'selectAllStaff',
            'subject': 'selectAllSubjects'
        };
        
        const selectAllId = idMap[entityType];
        if (!selectAllId) return;
        
        const selectAll = document.getElementById(selectAllId);
        if (!selectAll) return;
        
        const checkboxes = document.querySelectorAll('.row-checkbox-' + entityType);
        if (checkboxes.length === 0) return;
        
        checkboxes.forEach(cb => {
            if (cb) cb.checked = selectAll.checked;
        });
        updateBulkDeleteButton(entityType);
    }

    function updateBulkDeleteButton(entityType) {
        const checkboxes = document.querySelectorAll('.row-checkbox-' + entityType + ':checked');
        const btnIdMap = {
            'student': 'bulkDeleteStudentsBtn',
            'staff': 'bulkDeleteStaffBtn',
            'subject': 'bulkDeleteSubjectsBtn'
        };
        
        const btnId = btnIdMap[entityType];
        if (!btnId) return;
        
        const btn = document.getElementById(btnId);
        if (btn) btn.disabled = checkboxes.length === 0;
    }

    function confirmBulkDelete(entityType) {
        const checkboxes = document.querySelectorAll('.row-checkbox-' + entityType + ':checked');
        const count = checkboxes.length;
        if (count === 0) return;

        document.getElementById('bulk_delete_count').textContent = count;
        document.getElementById('bulk_delete_entity_type').value = entityType;
        const idsContainer = document.getElementById('bulkDeleteIds');
        idsContainer.innerHTML = '';
        
        checkboxes.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_ids[]';
            input.value = cb.value;
            idsContainer.appendChild(input);
        });

        new bootstrap.Modal(document.getElementById('bulkDeleteModal')).show();
    }
</script>

<?php include '../includes/footer.php'; ?>
