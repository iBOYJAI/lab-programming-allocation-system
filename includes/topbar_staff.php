<?php

/**
 * Staff Topbar - Redesign with Notion Icons
 */
// Get notifications for current user
$staff_id = $_SESSION['user_id'] ?? null;

// Ensure database connection is available
if (!isset($pdo)) {
    if (isset($conn)) {
        $pdo = $conn;
    } else {
        $pdo = getDbConnection();
    }
}
// Fetch fresh user data for avatar
$current_user = [];
if ($staff_id) {
    try {
        // Staff details are in 'staff' table but user login info might be in 'users'.
        // Assuming 'users' table holds the login credentials for all roles or 'staff' table has all info.
        // Based on view_staff.php, staff table has 'avatar_id', 'gender'.
        // Let's check if there is a 'users' table linking to staff or if logic differs.
        // Admin topbar uses 'users' table.
        // For now, let's assume 'staff' table is the source of truth for profile info if logged in as staff.
        // Or if 'users' table is the central auth table.
        // Let's try to fetch from 'staff' table first if 'users' fails or check session structure.
        // $_SESSION['user_role'] is 'staff'.

        $stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
        $stmt->execute([$staff_id]);
        $current_user = $stmt->fetch() ?: [];
    } catch (Exception $e) {
        // Silent fail
    }
}

$notifs = [];
$unread_count = 0;

if ($staff_id) {
    $notifs = dbQuery("
        SELECT * FROM notifications 
        WHERE (user_id = ? AND user_role = 'staff') OR (user_id IS NULL AND user_role = 'staff')
        ORDER BY created_at DESC LIMIT 5
    ", [$staff_id]);

    $unread_stats = dbQuery("
        SELECT COUNT(*) as count FROM notifications 
        WHERE ((user_id = ? AND user_role = 'staff') OR (user_id IS NULL AND user_role = 'staff'))
        AND is_read = 0
    ", [$staff_id]);
    $unread_count = $unread_stats[0]['count'] ?? 0;
}
?>
<header class="topbar-new glass-header">
    <div class="topbar-content container-fluid">
        <!-- Left Side: Toggle & Search -->
        <div class="d-flex align-items-center gap-4">
            <button class="btn-sidebar-toggle" id="sidebarToggleBtn">
                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-sidebar.svg" width="20" alt="Toggle">
            </button>
        </div>

        <!-- Right Side: Actions -->
        <div class="d-flex align-items-center gap-3">
            <!-- Notifications -->
            <div class="dropdown">
                <button class="btn-icon-action position-relative" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-bell.svg" width="20" alt="Notifications">
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge">
                            <?= $unread_count > 9 ? '9+' : $unread_count ?>
                        </span>
                    <?php endif; ?>
                </button>

                <ul class="dropdown-menu dropdown-menu-end notification-dropdown animate slideIn" aria-labelledby="notificationDropdown">
                    <li class="dropdown-header">
                        <div class="d-flex justify-content-between align-items-center w-100">
                            <span class="fw-bold">Notifications</span>
                            <?php if ($unread_count > 0): ?>
                                <a href="mark_read.php" class="text-xs text-primary text-decoration-none fw-bold">Mark all read</a>
                            <?php endif; ?>
                        </div>
                    </li>

                    <div class="notification-list custom-scrollbar">
                        <?php if (empty($notifs)): ?>
                            <div class="empty-state p-4 text-center">
                                <div class="mb-2 opacity-25">
                                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-bell-slash.svg" width="48">
                                </div>
                                <p class="small text-muted mb-0">No new notifications</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifs as $n): ?>
                                <li>
                                    <a class="dropdown-item notification-item <?= $n['is_read'] ? 'read' : 'unread' ?>" href="<?= $n['link'] ?: '#' ?>">
                                        <div class="notification-icon">
                                            <i class="bi <?= $n['icon'] ?: 'bi-bell' ?>"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-title"><?= sanitizeOutput($n['title']) ?></div>
                                            <div class="notification-body text-truncate" style="max-width: 200px;"><?= sanitizeOutput($n['message']) ?></div>
                                            <div class="notification-time"><?= getTimeElapsed($n['created_at']) ?></div>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <li class="dropdown-footer text-center p-2 border-top">
                        <a href="notifications.php" class="small fw-bold text-decoration-none text-primary">View All History</a>
                    </li>
                </ul>
            </div>

            <div class="vr mx-1 bg-secondary opacity-10 d-none d-sm-block" style="height: 24px;"></div>

            <!-- User Profile -->
            <div class="dropdown">
                <button class="btn-user-profile" id="userProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar-wrapper">
                        <?php
                        $nav_avatar = getProfileImage($current_user['gender'] ?? 'Male', $current_user['id'] ?? 0, $current_user['avatar_id'] ?? null);
                        ?>
                        <img src="../assets/images/Avatar/<?= $nav_avatar ?>" alt="User" class="user-avatar">
                        <span class="status-indicator online"></span>
                    </div>
                    <div class="user-info d-none d-sm-block">
                        <span class="user-name"><?= sanitizeOutput($_SESSION['name'] ?? 'Staff') ?></span>
                        <span class="user-role">Staff Member</span>
                    </div>
                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-chevron-down-circle.svg" width="14" class="ms-2 opacity-50">
                </button>

                <ul class="dropdown-menu dropdown-menu-end profile-dropdown animate slideIn" aria-labelledby="userProfileDropdown">
                    <li class="profile-header">
                        <div class="d-flex align-items-center gap-3">
                            <img src="../assets/images/Avatar/<?= $nav_avatar ?>" alt="User" class="profile-header-avatar">
                            <div>
                                <div class="fw-bold"><?= sanitizeOutput($_SESSION['name'] ?? 'Staff') ?></div>
                                <div class="small text-muted"><?= sanitizeOutput($_SESSION['email'] ?? 'staff@system.com') ?></div>
                            </div>
                        </div>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item" href="profile.php">
                            <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-user.svg" width="18" class="me-2 opacity-70"> My Profile
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="logout.php">
                            <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-power-off.svg" width="18" class="me-2 notion-icon-red"> Sign Out
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>