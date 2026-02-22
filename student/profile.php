<?php

/**
 * Student Profile - Premium Redesign
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['student'], '../auth.php');

$student_id = $_SESSION['user_id'];
$conn = getDbConnection();

// Update Avatar Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_avatar') {
    try {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Invalid security token.");
        }
        $avatar_id = $_POST['avatar_id'] ?? 1;
        $stmt = $conn->prepare("UPDATE students SET avatar_id = ? WHERE id = ?");
        $stmt->execute([$avatar_id, $student_id]);
        $_SESSION['success'] = "Avatar updated successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: profile.php");
    exit;
}

// Password Change Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_password') {
    try {
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Invalid security token.");
        }

        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($current_pass) || empty($new_pass)) {
            throw new Exception("All password fields are required.");
        }
        if ($new_pass !== $confirm_pass) {
            throw new Exception("New passwords do not match.");
        }
        if (strlen($new_pass) < 6) {
            throw new Exception("Password must be at least 6 characters.");
        }

        $stmt = $conn->prepare("SELECT password FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();

        if (!$student || !password_verify($current_pass, $student['password'])) {
            throw new Exception("Current password is incorrect.");
        }

        $hashed = hashPassword($new_pass);
        $stmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $student_id]);

        logActivity($student_id, 'student', 'change_password', "Changed own password");
        $_SESSION['success'] = "Password updated successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header("Location: profile.php");
    exit;
}

// Fetch student info
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student_data = $stmt->fetch();

$page_title = 'My Profile';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_student.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_student.php'; ?>

        <div class="main-content">
            <?php echo displayAlert(); ?>

            <div class="row g-4">
                <!-- Left Column: Sticky Profile Card -->
                <div class="col-lg-4">
                    <div class="card card-profile-glass sticky-top animate-fade-up" style="top: 100px; z-index: 10;">
                        <div class="card-notion-body p-4 text-center">
                            <div class="position-relative d-inline-block mb-4">
                                <div class="avatar-circle-xl shadow-lg border border-4 border-white">
                                    <img src="../assets/images/Avatar/<?= getProfileImage($student_data['gender'] ?? 'Male', $student_id, $student_data['avatar_id'] ?? null) ?>" width="120" id="currentAvatar">
                                </div>
                                <button class="btn btn-primary btn-sm rounded-circle position-absolute bottom-0 end-0 shadow" data-bs-toggle="modal" data-bs-target="#avatarModal">
                                    <i class="bi bi-camera-fill"></i>
                                </button>
                            </div>

                            <h4 class="fw-bold mb-1 text-dark"><?= sanitizeOutput($student_data['name']) ?></h4>
                            <div class="badge bg-light text-secondary border px-3 rounded-pill mb-4"><?= sanitizeOutput($student_data['roll_number']) ?></div>

                            <div class="profile-quick-stats text-start mb-4 p-3 rounded-4 bg-white bg-opacity-50">
                                <div class="mb-3">
                                    <label class="x-small fw-bold text-uppercase text-secondary d-block mb-1">Department</label>
                                    <p class="mb-0 text-dark fw-medium small text-truncate"><i class="bi bi-building me-2 text-primary"></i><?= sanitizeOutput($student_data['department'] ?? 'Information Technology') ?></p>
                                </div>
                                <div class="mb-0">
                                    <label class="x-small fw-bold text-uppercase text-secondary d-block mb-1">Academic Year</label>
                                    <p class="mb-0 text-dark fw-medium small"><i class="bi bi-mortarboard me-2 text-purple"></i>Semester <?= sanitizeOutput($student_data['semester'] ?? '3') ?></p>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button class="btn btn-white border rounded-pill fw-bold shadow-sm" onclick="window.print()">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>Export Profile
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Content Tabs -->
                <div class="col-lg-8">
                    <div class="card card-notion-glass animate-fade-up delay-100 mb-4 h-100">
                        <div class="card-header bg-transparent border-bottom p-0">
                            <ul class="nav nav-pills p-3 gap-2" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active rounded-pill px-4 fw-bold" data-bs-toggle="pill" href="#security">
                                        <i class="bi bi-shield-lock me-2"></i>Security
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link rounded-pill px-4 fw-bold" data-bs-toggle="pill" href="#activity">
                                        <i class="bi bi-clock-history me-2"></i>Activity
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link rounded-pill px-4 fw-bold" data-bs-toggle="pill" href="#privacy">
                                        <i class="bi bi-eye me-2"></i>Privacy
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-notion-body p-4">
                            <div class="tab-content">
                                <!-- Security Tab -->
                                <div class="tab-pane fade show active" id="security" role="tabpanel">
                                    <h5 class="fw-bold mb-4">Change Password</h5>
                                    <form method="POST" action="profile.php" class="glass-form p-4 rounded-4 border">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="update_password">

                                        <div class="mb-4">
                                            <label class="form-label small fw-bold text-uppercase text-secondary">Current Password</label>
                                            <div class="input-group-notion-glass">
                                                <input type="password" name="current_password" class="form-control" placeholder="Verify your current identity" required>
                                            </div>
                                        </div>

                                        <div class="row g-4 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold text-uppercase text-secondary">New Password</label>
                                                <div class="input-group-notion-glass">
                                                    <input type="password" name="new_password" class="form-control" placeholder="Min. 6 chars" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label small fw-bold text-uppercase text-secondary">Confirm New Password</label>
                                                <div class="input-group-notion-glass">
                                                    <input type="password" name="confirm_password" class="form-control" placeholder="Match new password" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="alert alert-info border-0 rounded-4 d-flex align-items-center gap-3 mb-4">
                                            <i class="bi bi-info-circle-fill text-primary fs-4"></i>
                                            <div class="small">Changing your password will secure your account across all devices.</div>
                                        </div>

                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">Update Security</button>
                                        </div>
                                    </form>
                                </div>

                                <!-- Activity Tab -->
                                <div class="tab-pane fade" id="activity" role="tabpanel">
                                    <h5 class="fw-bold mb-4">Recent Account Activity</h5>
                                    <div class="activity-timeline">
                                        <?php
                                        $logs = dbQuery("SELECT * FROM activity_logs WHERE user_id = ? AND user_role = 'student' ORDER BY created_at DESC LIMIT 10", [$student_id]);
                                        if (empty($logs)): ?>
                                            <div class="text-center py-5">
                                                <p class="text-muted">No recent activity detected.</p>
                                            </div>
                                        <?php else: ?>
                                            <?php foreach ($logs as $log): ?>
                                                <div class="d-flex gap-3 mb-4">
                                                    <div class="activity-icon-sm bg-light border rounded-circle flex-shrink-0" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="bi bi-dot text-primary fs-2"></i>
                                                    </div>
                                                    <div>
                                                        <p class="mb-0 fw-bold text-dark small"><?= sanitizeOutput($log['activity_type']) ?></p>
                                                        <p class="mb-1 text-secondary x-small"><?= sanitizeOutput($log['description']) ?></p>
                                                        <p class="text-muted x-small mb-0"><?= formatDate($log['created_at'], 'M j, H:i') ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Privacy Tab -->
                                <div class="tab-pane fade" id="privacy" role="tabpanel">
                                    <h5 class="fw-bold mb-4">Privacy & Data</h5>
                                    <div class="p-4 rounded-4 border bg-white bg-opacity-25 shadow-sm mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h6 class="fw-bold mb-1">Public Profile</h6>
                                                <p class="small text-secondary mb-0">Allow staff to view your full performance history.</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-switch" type="checkbox" checked disabled>
                                            </div>
                                        </div>
                                        <hr class="opacity-10">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="fw-bold mb-1">Session Data</h6>
                                                <p class="small text-secondary mb-0">Manage active sessions and cookies.</p>
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger border-0 rounded-pill">Clear All</button>
                                        </div>
                                    </div>
                                    <div class="alert alert-warning border-0 rounded-4 small">
                                        <strong>Note:</strong> Your academic data is managed by the institution. Detailed progress is visible only to you and assigned staff members.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Avatar Modal -->
<div class="modal fade" id="avatarModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold text-dark">Choose Your Avatar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" action="profile.php">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="update_avatar">

                    <div class="row g-3">
                        <?php
                        $max_avatars = ($student_data['gender'] == 'Female' ? 4 : 7);
                        $prefix = ($student_data['gender'] == 'Female' ? 'girl' : 'boy');
                        for ($i = 1; $i <= $max_avatars; $i++):
                        ?>
                            <div class="col-3 text-center">
                                <label class="avatar-selector">
                                    <input type="radio" name="avatar_id" value="<?= $i ?>" <?= (($student_data['avatar_id'] ?? 1) == $i) ? 'checked' : '' ?>>
                                    <div class="avatar-option">
                                        <img src="../assets/images/Avatar/<?= $prefix . '-' . $i . '.png' ?>" class="img-fluid rounded-circle">
                                    </div>
                                </label>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill fw-bold">Update Avatar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    :root {
        --glass-bg: rgba(255, 255, 255, 0.7);
        --glass-border: rgba(255, 255, 255, 0.4);
        --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);
    }

    .card-profile-glass,
    .card-notion-glass {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        box-shadow: var(--glass-shadow);
        border-radius: 24px;
        overflow: hidden;
    }

    .avatar-circle-xl {
        width: 120px;
        height: 120px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .nav-pills .nav-link {
        color: #6c757d;
        transition: all 0.3s ease;
    }

    .nav-pills .nav-link.active {
        background: var(--bs-primary);
        color: white;
        box-shadow: 0 4px 15px rgba(var(--bs-primary-rgb), 0.3);
    }

    .input-group-notion-glass .form-control {
        background: rgba(255, 255, 255, 0.5);
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 12px;
        padding: 12px 16px;
        transition: all 0.3s ease;
    }

    .input-group-notion-glass .form-control:focus {
        background: white;
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), 0.1);
    }

    .glass-form {
        background: rgba(255, 255, 255, 0.3);
    }

    .avatar-selector input {
        display: none;
    }

    .avatar-option {
        padding: 5px;
        border-radius: 50%;
        border: 3px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
    }

    .avatar-selector input:checked+.avatar-option {
        border-color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.1);
        transform: scale(1.1);
    }

    @media print {
        .app-container {
            padding: 0;
        }

        .sidebar,
        .topbar,
        .btn,
        .nav,
        .modal {
            display: none !important;
        }

        .main-wrapper {
            margin: 0;
        }

        .card-profile-glass {
            position: static !important;
            border: none;
            box-shadow: none;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>