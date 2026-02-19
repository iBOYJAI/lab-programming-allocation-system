<?php

/**
 * Admin Topbar - Redesign with Notion Icons
 */
// Get notifications for current user
$admin_id = $_SESSION['user_id'] ?? null;

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
if ($admin_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$admin_id]);
        $current_user = $stmt->fetch() ?: [];
    } catch (Exception $e) {
        // Silent fail
    }
}
$notifs = [];
$unread_count = 0;

if ($admin_id) {
    $notifs = dbQuery("
        SELECT * FROM notifications 
        WHERE (user_id = ? AND user_role = 'admin') OR (user_id IS NULL AND user_role = 'admin')
        ORDER BY created_at DESC LIMIT 5
    ", [$admin_id]);

    $unread_stats = dbQuery("
        SELECT COUNT(*) as count FROM notifications 
        WHERE ((user_id = ? AND user_role = 'admin') OR (user_id IS NULL AND user_role = 'admin'))
        AND is_read = 0
    ", [$admin_id]);
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

            <div class="search-wrapper d-none d-md-block">
                <form action="search.php" method="GET" class="search-form" id="searchBar">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="q" id="searchInput"
                        class="search-input"
                        placeholder="Search system..."
                        value="<?= sanitizeOutput($_GET['q'] ?? '') ?>"
                        autocomplete="off">
                </form>
                <div id="searchSuggestions" class="search-suggestions-dropdown d-none"></div>
            </div>
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
                        <span class="user-name"><?= sanitizeOutput($_SESSION['username'] ?? 'Admin') ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-chevron-down-circle.svg" width="14" class="ms-2 opacity-50">
                </button>

                <ul class="dropdown-menu dropdown-menu-end profile-dropdown animate slideIn" aria-labelledby="userProfileDropdown">
                    <li class="profile-header">
                        <div class="d-flex align-items-center gap-3">
                            <img src="../assets/images/Avatar/<?= $nav_avatar ?>" alt="User" class="profile-header-avatar">
                            <div>
                                <div class="fw-bold"><?= sanitizeOutput($_SESSION['username'] ?? 'Admin') ?></div>
                                <div class="small text-muted"><?= sanitizeOutput($_SESSION['email'] ?? 'admin@system.com') ?></div>
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
                        <a class="dropdown-item" href="settings.php">
                            <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-settings.svg" width="18" class="me-2 opacity-70"> System Settings
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="logs.php">
                            <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-clipboard-list-check.svg" width="18" class="me-2 opacity-70"> Activity Logs
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