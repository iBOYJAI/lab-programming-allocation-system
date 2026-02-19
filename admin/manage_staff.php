<?php

/**
 * Staff Management - Premium Design
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

// --- Helper Functions ---

/**
 * Get staff list with filtering and pagination
 */
function getStaffList($conn, $search = '', $status = 'all', $limit = 10, $offset = 0)
{
    $where = [];
    $params = [];

    if (!empty($search)) {
        $where[] = "(name LIKE ? OR email LIKE ? OR staff_id LIKE ?)";
        $term = "%$search%";
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    if ($status !== 'all') {
        $where[] = "status = ?";
        $params[] = $status;
    }

    $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

    // Get Total Count
    $countSql = "SELECT COUNT(*) as total FROM staff $whereClause";
    $stmt = $conn->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get Data
    $sql = "SELECT * FROM staff $whereClause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
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
$filter_status = $_GET['status'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        $conn = getDbConnection();

        try {
            if ($action === 'add') {
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $password = $_POST['password'];
                $gender = $_POST['gender'] ?? 'Male';
                $staff_status = $_POST['staff_status'] ?? 'Active'; // Default to Active

                if (empty($name) || empty($password)) {
                    throw new Exception("Name and password are required.");
                }

                $staff_id = generateStaffId();
                $hashed = hashPassword($password);

                $sql = "INSERT INTO staff (staff_id, name, email, phone, password_hash, status, gender) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$staff_id, $name, $email, $phone, $hashed, $staff_status, $gender]);

                logActivity($_SESSION['user_id'], 'admin', 'add_staff', "Added staff: $name ($staff_id)");
                $_SESSION['success'] = "Staff added successfully! ID: $staff_id";
            } elseif ($action === 'edit') {
                $id = intval($_POST['staff_db_id']);
                $name = trim($_POST['name']);
                $email = trim($_POST['email']);
                $phone = trim($_POST['phone']);
                $gender = $_POST['gender'];
                $staff_status = $_POST['staff_status'];

                $sql = "UPDATE staff SET name = ?, email = ?, phone = ?, status = ?, gender = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$name, $email, $phone, $staff_status, $gender, $id]);

                logActivity($_SESSION['user_id'], 'admin', 'edit_staff', "Updated staff ID: $id");
                $_SESSION['success'] = "Staff updated successfully.";
            } elseif ($action === 'delete') {
                $id = intval($_POST['staff_db_id']);

                $sql = "DELETE FROM staff WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$id]);

                logActivity($_SESSION['user_id'], 'admin', 'delete_staff', "Deleted staff ID: $id");
                $_SESSION['success'] = "Staff deleted successfully.";
            } elseif ($action === 'import_csv') {
                if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Please upload a valid CSV file.");
                }

                $file = $_FILES['csv_file']['tmp_name'];
                $handle = fopen($file, "r");
                $header = fgetcsv($handle);
                $imported = 0;
                $errors = 0;

                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) < 4) continue;
                    $name = trim($data[0]);
                    $email = trim($data[1]);
                    $phone = trim($data[2]);
                    $pass = trim($data[3]);
                    $gender = trim($data[4] ?? 'Male');

                    if (empty($name) || empty($pass)) {
                        $errors++;
                        continue;
                    }

                    $staff_id = generateStaffId();
                    $hashed = hashPassword($pass);

                    $sql = "INSERT INTO staff (staff_id, name, email, phone, password_hash, status, gender) VALUES (?, ?, ?, ?, ?, 'Active', ?)";
                    $stmt = $conn->prepare($sql);
                    try {
                        if ($stmt->execute([$staff_id, $name, $email, $phone, $hashed, $gender])) {
                            $imported++;
                        } else {
                            $errors++;
                        }
                    } catch (Exception $e) {
                        $errors++;
                    }
                }
                fclose($handle);
                $_SESSION['success'] = "Import complete! $imported staff members added." . ($errors > 0 ? " ($errors skipped)" : "");
                logActivity($_SESSION['user_id'], 'admin', 'import_staff', "Imported $imported staff via CSV");
            }

            // Redirect to avoid resubmission
            $redirect_url = "manage_staff.php?page=$page&search=" . urlencode($search) . "&status=$filter_status&limit=$items_per_page";
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
$result = getStaffList($conn, $search, $filter_status, $items_per_page, $offset);
$staff_list = $result['data'];
$total_records = $result['total'];
$total_pages = ceil($total_records / $items_per_page);

$page_title = 'Staff Management';
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
                    <h1 class="fw-bold mb-1 text-dark fs-3">Staff Directory</h1>
                    <p class="text-secondary mb-0">Manage teaching staff, roles, and system access.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-white border shadow-sm rounded-pill px-4 fw-medium" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="bi bi-file-earmark-arrow-up me-2"></i>Import CSV
                    </button>
                    <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                        <i class="bi bi-person-plus-fill me-2"></i>Add Staff
                    </button>
                </div>
            </div>

            <?php echo displayAlert(); ?>

            <!-- Interactive Toolbar -->
            <div class="card-notion mb-4 animate-fade-up delay-100">
                <div class="card-notion-body p-3">
                    <form method="GET" class="row g-3 align-items-end" id="filterForm">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold text-uppercase text-muted">Search Member</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Name, Email, or ID..." value="<?= sanitizeOutput($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Status</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Status</option>
                                <option value="Active" <?= $filter_status === 'Active' ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= $filter_status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
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
                    <?php if (empty($staff_list)): ?>
                        <div class="text-center py-5">
                            <div class="opacity-25 mb-3">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-users.svg" width="60">
                            </div>
                            <h5 class="text-dark fw-bold mb-2">No staff members found</h5>
                            <p class="text-muted small">Try refreshing the page or clearing your filters.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 text-uppercase text-secondary" style="font-size: 0.75rem;">Member</th>
                                        <th class="text-uppercase text-secondary" style="font-size: 0.75rem;">Contact</th>
                                        <th class="text-uppercase text-secondary" style="font-size: 0.75rem;">Status</th>
                                        <th class="text-uppercase text-secondary" style="font-size: 0.75rem;">Joined</th>
                                        <th class="text-end pe-4 text-uppercase text-secondary" style="font-size: 0.75rem;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff_list as $s):
                                        $avatar_file = getProfileImage($s['gender'] ?? 'Male', $s['id'], $s['avatar_id'] ?? null);
                                        $status_class = ($s['status'] ?? 'Active') === 'Active' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary';
                                    ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <img src="../assets/images/Avatar/<?= $avatar_file ?>" alt="Avatar" class="rounded-circle shadow-sm" width="40" height="40">
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark text-truncate" style="max-width: 200px;"><?= sanitizeOutput($s['name']) ?></div>
                                                        <div class="small text-muted font-monospace"><?= sanitizeOutput($s['staff_id']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-truncate small text-dark"><i class="bi bi-envelope me-2 text-muted"></i><?= sanitizeOutput($s['email'] ?? 'N/A') ?></span>
                                                    <span class="text-truncate small text-muted"><i class="bi bi-telephone me-2 text-muted"></i><?= sanitizeOutput($s['phone'] ?? 'N/A') ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill <?= $status_class ?> px-3 fw-medium">
                                                    <i class="bi bi-dot me-1"></i><?= $s['status'] ?? 'Active' ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small">
                                                <?= date('M d, Y', strtotime($s['created_at'])) ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <a href="view_staff.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold" title="View Profile">
                                                        <i class="bi bi-eye text-primary me-1"></i>View
                                                    </a>
                                                    <button class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold" onclick="editStaff(<?= htmlspecialchars(json_encode($s)) ?>)" title="Edit Details">
                                                        <i class="bi bi-pencil text-warning me-1"></i>Edit
                                                    </button>
                                                    <button class="btn btn-sm btn-white border rounded-pill px-3 shadow-sm hover-bg-light fw-bold text-danger" onclick="confirmDelete(<?= $s['id'] ?>, '<?= sanitizeOutput($s['name']) ?>')" title="Delete Member">
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
                                    Showing <strong><?= $offset + 1 ?></strong> to <strong><?= min($offset + $items_per_page, $total_records) ?></strong> of <strong><?= $total_records ?></strong> staff
                                </div>

                                <?php if ($total_pages > 1): ?>
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                                <a class="page-link border-0 text-dark" href="<?= ($page > 1) ? "?page=" . ($page - 1) . "&limit=$items_per_page&search=$search&status=$filter_status" : '#' ?>">
                                                    &laquo; Prev
                                                </a>
                                            </li>

                                            <?php
                                            $range = 2;
                                            $start = max(1, $page - $range);
                                            $end = min($total_pages, $page + $range);

                                            if ($start > 1) {
                                                echo '<li class="page-item"><a class="page-link border-0 text-dark" href="?page=1&limit=' . $items_per_page . '&search=' . $search . '&status=' . $filter_status . '">1</a></li>';
                                                if ($start > 2) echo '<li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>';
                                            }

                                            for ($i = $start; $i <= $end; $i++) {
                                                $active = ($i == $page) ? 'bg-primary text-white rounded' : 'text-dark';
                                                echo '<li class="page-item"><a class="page-link border-0 ' . $active . ' mx-1" href="?page=' . $i . '&limit=' . $items_per_page . '&search=' . $search . '&status=' . $filter_status . '">' . $i . '</a></li>';
                                            }

                                            if ($end < $total_pages) {
                                                if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link border-0 text-muted">...</span></li>';
                                                echo '<li class="page-item"><a class="page-link border-0 text-dark" href="?page=' . $total_pages . '&limit=' . $items_per_page . '&search=' . $search . '&status=' . $filter_status . '">' . $total_pages . '</a></li>';
                                            }
                                            ?>

                                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                                <a class="page-link border-0 text-dark" href="<?= ($page < $total_pages) ? "?page=" . ($page + 1) . "&limit=$items_per_page&search=$search&status=$filter_status" : '#' ?>">
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

<!-- Modal: Add Staff -->
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

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Dr. John Doe" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="john@example.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="+1234567890">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="row g-3">
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
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Add Member</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Staff -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Edit Staff Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editStaffForm">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="staff_db_id" id="edit_staff_id">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Full Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Phone</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control">
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Gender</label>
                            <select name="gender" id="edit_gender" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary text-uppercase">Status</label>
                            <select name="staff_status" id="edit_status" class="form-select">
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

<!-- Modal: Delete Confirmation -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body text-center pt-5 pb-4 px-4">
                <div class="mb-3 text-danger">
                    <i class="bi bi-exclamation-circle display-1"></i>
                </div>
                <h5 class="fw-bold mb-2">Are you sure?</h5>
                <p class="text-muted mb-4 opacity-75">Do you really want to delete <strong id="delete_staff_name" class="text-dark"></strong>? This cannot be undone.</p>

                <form method="POST" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="staff_db_id" id="delete_staff_id">

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-danger rounded-pill">Yes, Delete It</button>
                        <button type="button" class="btn btn-light text-muted rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Import CSV -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h5 class="modal-title fw-bold">Bulk Import Staff</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="import_csv">

                    <div class="alert bg-blue-subtle text-blue border-0 small mb-4">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        Please upload a CSV file with:
                        <code class="d-block mt-1">name, email, phone, password, gender</code>
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
    function editStaff(data) {
        document.getElementById('edit_staff_id').value = data.id;
        document.getElementById('edit_name').value = data.name;
        document.getElementById('edit_email').value = data.email || '';
        document.getElementById('edit_phone').value = data.phone || '';
        document.getElementById('edit_status').value = data.status || 'Active';
        document.getElementById('edit_gender').value = data.gender || 'Male';

        const modal = new bootstrap.Modal(document.getElementById('editStaffModal'));
        modal.show();
    }

    function confirmDelete(id, name) {
        document.getElementById('delete_staff_id').value = id;
        document.getElementById('delete_staff_name').textContent = name;

        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    // Auto-trigger edit if requested
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('action_trigger') === 'edit') {
            <?php if (!empty($staff_list)): ?>
                editStaff(<?= json_encode($staff_list[0]) ?>);
            <?php endif; ?>
        }
    });
</script>

<?php include '../includes/footer.php'; ?>