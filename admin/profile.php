<?php

/**
 * Admin Profile Management
 * Allows admins to update their details and avatar
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

$page_title = 'My Profile';
$conn = getDbConnection();
$user_id = $_SESSION['user_id'];
$message = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            throw new Exception("Invalid security token detected.");
        }

        $gender = $_POST['gender'] ?? 'Male';
        $avatar_id = !empty($_POST['selected_avatar']) ? intval($_POST['selected_avatar']) : null;
        $username = trim($_POST['username']);

        // Prepare base update query
        $sql = "UPDATE users SET username = ?, gender = ?, avatar_id = ? WHERE id = ?";
        $params = [$username, $gender, $avatar_id, $user_id];

        // Handle password update if provided
        if (!empty($_POST['new_password'])) {
            $new_pass = $_POST['new_password'];
            $confirm_pass = $_POST['confirm_password'];

            if ($new_pass !== $confirm_pass) {
                throw new Exception("New passwords do not match.");
            }
            if (strlen($new_pass) < 6) {
                throw new Exception("Password must be at least 6 characters.");
            }

            // Append password update
            $sql = "UPDATE users SET username = ?, gender = ?, avatar_id = ?, password = ? WHERE id = ?";
            $hashed = hashPassword($new_pass);
            $params = [$username, $gender, $avatar_id, $hashed, $user_id];
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        // Update Session
        $_SESSION['username'] = $username;
        $_SESSION['success'] = "Profile updated successfully!";

        // Refresh to see changes
        header("Location: profile.php");
        exit;
    } catch (Exception $e) {
        $message = showError($e->getMessage());
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// Current Avatar
$current_gender = $user['gender'] ?? 'Male';
$current_avatar = getProfileImage($current_gender, $user['id'], $user['avatar_id'] ?? null);

include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <div class="row align-items-center mb-4">
                <div class="col">
                    <h1 class="page-title mb-1 text-dark">My Profile</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Profile</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <?= displayAlert() ?>
            <?= $message ?>

            <div class="row">
                <!-- Profile Information Card -->
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-bottom p-4">
                            <h5 class="fw-bold mb-0 text-dark">Profile Settings</h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="" id="profileForm">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="selected_avatar" id="selected_avatar" value="<?= $user['avatar_id'] ?? '' ?>">

                                <div class="row mb-5">
                                    <div class="col-md-4 text-center">
                                        <div class="mb-3 position-relative d-inline-block">
                                            <img src="../assets/images/Avatar/<?= $current_avatar ?>" id="avatarPreview" class="rounded-circle shadow" width="150" height="150" alt="Profile">
                                            <button type="button" class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle shadow" style="width: 32px; height: 32px;" data-bs-toggle="modal" data-bs-target="#avatarModal">
                                                <i class="bi bi-pencil-fill" style="font-size: 0.8rem;"></i>
                                            </button>
                                        </div>
                                        <h5 class="fw-bold text-dark"><?= sanitizeOutput($user['username']) ?></h5>
                                        <p class="text-muted small">Administrator</p>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label class="form-label small fw-bold text-uppercase text-muted">Full Name</label>
                                            <input type="text" name="username" class="form-control" value="<?= sanitizeOutput($user['username']) ?>" required>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label small fw-bold text-uppercase text-muted">Gender</label>
                                                <select name="gender" class="form-select" id="genderSelect">
                                                    <option value="Male" <?= $current_gender == 'Male' ? 'selected' : '' ?>>Male</option>
                                                    <option value="Female" <?= $current_gender == 'Female' ? 'selected' : '' ?>>Female</option>
                                                </select>
                                                <small class="text-muted d-block mt-1 x-small">Changing gender will reset avatar availability.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <h6 class="fw-bold text-dark mb-3">Security</h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-uppercase text-muted">New Password</label>
                                        <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-uppercase text-muted">Confirm Password</label>
                                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="bi bi-save me-2"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Account Status Card -->
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-3">Account Details</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item px-0 py-3 border-bottom d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Role</span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">Administrator</span>
                                </li>
                                <li class="list-group-item px-0 py-3 border-bottom d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Status</span>
                                    <span class="badge bg-success bg-opacity-10 text-success">Active</span>
                                </li>
                                <li class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                    <span class="text-muted small">Last Active</span>
                                    <span class="text-dark small fw-bold"><?= getTimeElapsed(date('Y-m-d H:i:s', $_SESSION['last_activity'] ?? time())) ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Avatar Selection Modal -->
<div class="modal fade" id="avatarModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Choose an Avatar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Previous selections for your gender.</p>
                <div class="row g-3" id="avatarGrid">
                    <!-- Avatars will be injected here via JS -->
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary rounded-pill px-4" id="confirmAvatar">Confirm Selection</button>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-option {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid transparent;
        border-radius: 50%;
        padding: 4px;
    }

    .avatar-option:hover {
        transform: scale(1.1);
        background: #f8f9fa;
    }

    .avatar-option.selected {
        border-color: #4f46e5;
        background: rgba(79, 70, 229, 0.1);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const genderSelect = document.getElementById('genderSelect');
        const avatarGrid = document.getElementById('avatarGrid');
        const selectedAvatarInput = document.getElementById('selected_avatar');
        const avatarPreview = document.getElementById('avatarPreview');
        const confirmAvatarBtn = document.getElementById('confirmAvatar');
        const avatarModal = new bootstrap.Modal(document.getElementById('avatarModal'));

        // Avatar configuration - based on existing files in assets/images/Avatar/
        const avatars = {
            'Male': [1, 2, 3, 4, 5, 6, 7], // boy-1.png through boy-7.png
            'Female': [1, 2, 3, 4] // girl-1.png through girl-4.png
        };

        let tempSelectedId = selectedAvatarInput.value || null;

        function renderAvatars(gender) {
            const available = avatars[gender] || avatars['Male'];
            const prefix = gender === 'Female' ? 'girl' : 'boy';
            avatarGrid.innerHTML = available.map(id => `
                <div class="col-4 text-center">
                    <img src="../assets/images/Avatar/${prefix}-${id}.png" 
                         class="img-fluid rounded-circle avatar-option ${id == tempSelectedId ? 'selected' : ''}" 
                         data-id="${id}"
                         onclick="selectAvatar(this, ${id})">
                </div>
            `).join('');
        }

        window.selectAvatar = function(el, id) {
            document.querySelectorAll('.avatar-option').forEach(img => img.classList.remove('selected'));
            el.classList.add('selected');
            tempSelectedId = id;
        };

        // When modal opens
        document.getElementById('avatarModal').addEventListener('show.bs.modal', function() {
            renderAvatars(genderSelect.value);
            // If currently selected ID is not in the new gender list, reset visual selection
            const currentGender = genderSelect.value;
            const validIds = avatars[currentGender];
            if (tempSelectedId && !validIds.includes(parseInt(tempSelectedId))) {
                tempSelectedId = null; // Reset selection if mismatched gender
                renderAvatars(currentGender);
            }
        });

        // Confirm Selection
        confirmAvatarBtn.addEventListener('click', function() {
            if (tempSelectedId) {
                selectedAvatarInput.value = tempSelectedId;
                const currentGender = genderSelect.value;
                const prefix = currentGender === 'Female' ? 'girl' : 'boy';
                avatarPreview.src = `../assets/images/Avatar/${prefix}-${tempSelectedId}.png`;
            }
            avatarModal.hide();
        });

        // Gender change listener
        genderSelect.addEventListener('change', function() {
            // Reset selected avatar if gender changes
            selectedAvatarInput.value = '';
            tempSelectedId = null;
            // You might want to auto-update the preview to a default for that gender, 
            // but for now let's just leave it or reset it? 
            // Better UX: Show a default random one or keep current until save.
            // Let's just re-render grid on next modal open.
        });
    });
</script>

<?php include '../includes/footer.php'; ?>