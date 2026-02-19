<?php

/**
 * Notifications Center - Admin
 * Premium Redesign
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

$admin_id = $_SESSION['user_id'];

// Fetch all notifications
$notifs = dbQuery("
    SELECT * FROM notifications 
    WHERE (user_id = ? AND user_role = 'admin') OR (user_id IS NULL AND user_role = 'admin')
    ORDER BY created_at DESC 
    LIMIT 100
", [$admin_id]);

$page_title = 'Communications Center';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
                <div>
                    <h1 class="fw-bold mb-1 text-dark fs-3">Communications Center</h1>
                    <p class="text-secondary mb-0">System alerts and administrative notifications.</p>
                </div>
                <a href="mark_read.php" class="btn btn-outline-primary fw-bold px-4 rounded-pill">
                    <i class="bi bi-check2-all me-1"></i> Mark All Read
                </a>
            </div>

            <div class="card-notion animate-fade-up delay-100">
                <div class="card-notion-body p-0">
                    <?php if (empty($notifs)): ?>
                        <div class="text-center py-5">
                            <div class="opacity-25 mb-3">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-bell-slash.svg" width="64">
                            </div>
                            <h5 class="text-dark fw-bold mb-1">No notifications found</h5>
                            <p class="text-muted">You are all caught up!</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifs as $n): ?>
                                <div class="list-group-item p-4 d-flex gap-4 border-bottom <?= $n['is_read'] ? '' : 'bg-light bg-opacity-50' ?>">
                                    <div class="flex-shrink-0">
                                        <div class="bg-white border rounded-circle d-flex align-items-center justify-content-center text-primary shadow-sm" style="width: 48px; height: 48px;">
                                            <i class="bi <?= $n['icon'] ?: 'bi-bell' ?> fs-5"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="fw-bold text-dark mb-0 fs-6"><?= sanitizeOutput($n['title']) ?></h6>
                                            <span class="text-muted small" style="font-size: 0.75rem;"><?= formatDate($n['created_at'], 'd M, H:i') ?></span>
                                        </div>
                                        <p class="text-secondary mb-2 small"><?= sanitizeOutput($n['message']) ?></p>
                                        <?php if ($n['link']): ?>
                                            <a href="<?= $n['link'] ?>" class="btn btn-sm btn-light border fw-bold text-primary px-3 rounded-pill">
                                                Take Action <i class="bi bi-arrow-right ms-1"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$n['is_read']): ?>
                                        <div class="my-auto text-primary">
                                            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>