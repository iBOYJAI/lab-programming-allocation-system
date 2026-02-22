<?php

/**
 * Manage Labs - Premium Redesign
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
        redirect('manage_labs.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['lab_name']);
        $total = intval($_POST['total_systems']);

        if (empty($name) || $total < 1) {
            $_SESSION['error'] = 'Please provide valid lab details';
        } else {
            $sql = "INSERT INTO labs (lab_name, total_systems) VALUES (?, ?)";
            if (dbExecute($sql, [$name, $total])) {
                logActivity($_SESSION['user_id'], 'admin', 'add_lab', "Added lab: $name");
                $_SESSION['success'] = 'Lab added successfully';
            } else {
                $_SESSION['error'] = 'Failed to add lab';
            }
        }
        redirect('manage_labs.php');
    } elseif ($action === 'edit') {
        $id = intval($_POST['lab_id']);
        $name = trim($_POST['lab_name']);
        $total = intval($_POST['total_systems']);

        $sql = "UPDATE labs SET lab_name = ?, total_systems = ? WHERE id = ?";
        if (dbExecute($sql, [$name, $total, $id])) {
            logActivity($_SESSION['user_id'], 'admin', 'edit_lab', "Updated lab: $name");
            $_SESSION['success'] = 'Lab updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update lab';
        }
        redirect('manage_labs.php');
    } elseif ($action === 'delete') {
        $id = intval($_POST['lab_id']);
        $sql = "DELETE FROM labs WHERE id = ?";
        if (dbExecute($sql, [$id])) {
            logActivity($_SESSION['user_id'], 'admin', 'delete_lab', "Deleted lab ID: $id");
            $_SESSION['success'] = 'Lab deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete lab';
        }
        redirect('manage_labs.php');
    } elseif ($action === 'bulk_delete') {
        $ids = $_POST['selected_ids'] ?? [];
        if (empty($ids)) {
            $_SESSION['error'] = 'No labs selected for deletion.';
        } else {
            $conn = getDbConnection();
            $conn->beginTransaction();
            try {
                $deleted_count = 0;
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $sql = "DELETE FROM labs WHERE id IN ($placeholders)";
                $stmt = $conn->prepare($sql);
                if ($stmt->execute($ids)) {
                    $deleted_count = $stmt->rowCount();
                }
                $conn->commit();
                logActivity($_SESSION['user_id'], 'admin', 'bulk_delete_labs', "Bulk deleted $deleted_count labs");
                $_SESSION['success'] = "$deleted_count lab(s) deleted successfully.";
            } catch (Exception $e) {
                $conn->rollBack();
                $_SESSION['error'] = 'Failed to delete labs: ' . $e->getMessage();
            }
        }
        redirect('manage_labs.php');
    }
}

$labs = dbQuery("SELECT * FROM labs ORDER BY lab_name");
$page_title = 'Manage Labs';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
                <div>
                    <h1 class="fw-bold mb-1 text-dark fs-3">Lab Infrastructure</h1>
                    <p class="text-secondary mb-0">Configure and monitor physical laboratory spaces.</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addLabModal">
                    <i class="bi bi-plus-lg me-2"></i> Register New Lab
                </button>
            </div>

            <?php echo displayAlert(); ?>

            <div class="row g-4 mb-4">
                <?php if (empty($labs)): ?>
                    <div class="col-12">
                        <div class="card-notion py-5 text-center animate-fade-up delay-100">
                            <div class="opacity-25 mb-3">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-building.svg" width="80">
                            </div>
                            <h4 class="text-dark fw-bold mb-2">No labs found</h4>
                            <p class="text-muted mb-0">No labs registered in the system yet.</p>
                            <button class="btn btn-link text-primary fw-bold text-decoration-none mt-3" data-bs-toggle="modal" data-bs-target="#addLabModal">
                                Register your first lab
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($labs as $lab): ?>
                        <div class="col-xl-4 col-md-6">
                            <div class="card-notion h-100 animate-fade-up delay-100">
                                <div class="card-notion-body p-4 d-flex flex-column h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4 shadow-sm">
                                            <i class="bi bi-pc-display fs-4"></i>
                                        </div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <input type="checkbox" class="form-check-input row-checkbox" value="<?= $lab['id'] ?>" onchange="updateBulkDeleteButton()" style="width: 18px; height: 18px;">
                                            <button class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" onclick="editLab(<?= htmlspecialchars(json_encode($lab)) ?>)" title="Edit Lab">
                                                <i class="bi bi-pencil text-warning"></i>
                                            </button>
                                            <button class="btn btn-sm btn-white border rounded-circle shadow-sm hover-bg-light" onclick="deleteLab(<?= $lab['id'] ?>, '<?= sanitizeOutput($lab['lab_name']) ?>')" title="Delete Lab">
                                                <i class="bi bi-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <h5 class="fw-bold text-dark mb-1"><?= sanitizeOutput($lab['lab_name']) ?></h5>
                                    <div class="mb-4">
                                        <span class="badge bg-light text-secondary border">
                                            <i class="bi bi-cpu me-1"></i> <?= $lab['total_systems'] ?> Workstations
                                        </span>
                                    </div>

                                    <div class="mt-auto d-flex align-items-center justify-content-between pt-3 border-top">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="status-indicator online"></span>
                                            <span class="small text-success fw-bold">Operational</span>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.75rem;">Created <?= formatDate($lab['created_at'], 'M Y') ?></small>
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

<!-- Add Lab Modal -->
<div class="modal fade" id="addLabModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">Register New Lab</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Lab Name</label>
                        <input type="text" name="lab_name" class="form-control" required placeholder="e.g. Advanced AI & ML Lab">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Capacity</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-pc-display"></i></span>
                            <input type="number" name="total_systems" class="form-control border-start-0" required min="1" value="30">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 rounded-pill">Create Lab</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Lab Modal -->
<div class="modal fade" id="editLabModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                    <h5 class="modal-title fw-bold">Modify Lab Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="lab_id" id="edit_lab_id">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Lab Name</label>
                        <input type="text" name="lab_name" id="edit_lab_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary text-uppercase">Total Workstations</label>
                        <input type="number" name="total_systems" id="edit_total_systems" class="form-control" required min="1">
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

<!-- Delete Lab Form -->
<form method="POST" id="deleteLabForm" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    <input type="hidden" name="lab_id" id="delete_lab_id">
</form>

<script>
    function editLab(lab) {
        document.getElementById('edit_lab_id').value = lab.id;
        document.getElementById('edit_lab_name').value = lab.lab_name;
        document.getElementById('edit_total_systems').value = lab.total_systems;
        new bootstrap.Modal(document.getElementById('editLabModal')).show();
    }

    function deleteLab(id, name) {
        if (confirm('Permanently decommission lab "' + name + '"?\n\nThis will also remove all historical data associated with this lab.')) {
            document.getElementById('delete_lab_id').value = id;
            document.getElementById('deleteLabForm').submit();
        }
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
                <h5 class="fw-bold mb-2">Delete Selected Labs?</h5>
                <p class="text-muted mb-4 opacity-75">Are you sure you want to delete <strong id="bulk_delete_count" class="text-dark">0</strong> selected lab(s)? This cannot be undone.</p>

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