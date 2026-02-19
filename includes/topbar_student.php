<?php

/**
 * Student Topbar - Redesign with Notion Icons
 */
$student_id = $_SESSION['user_id'] ?? null;
$conn = getDbConnection();

// Fetch fresh student data for avatar
$current_student = [];
if ($student_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $current_student = $stmt->fetch() ?: [];
    } catch (Exception $e) {
    }
}

$notifs = [];
$unread_count = 0;

if ($student_id) {
    $notifs = dbQuery("
        SELECT * FROM notifications 
        WHERE (user_id = ? AND user_role = 'student') OR (user_id IS NULL AND user_role = 'student')
        ORDER BY created_at DESC LIMIT 5
    ", [$student_id]);

    $unread_stats = dbQuery("
        SELECT COUNT(*) as count FROM notifications 
        WHERE ((user_id = ? AND user_role = 'student') OR (user_id IS NULL AND user_role = 'student'))
        AND is_read = 0
    ", [$student_id]);
    $unread_count = $unread_stats[0]['count'] ?? 0;
}
?>
<header class="topbar-new glass-header">
    <div class="topbar-content container-fluid">
        <div class="d-flex align-items-center gap-4">
            <button class="btn-sidebar-toggle" id="sidebarToggleBtn">
                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-sidebar.svg" width="20" alt="Toggle">
            </button>
        </div>

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
                                    <a class="dropdown-item notification-item <?= $n['is_read'] ? 'read' : 'unread' ?>" href="#">
                                        <div class="notification-icon">
                                            <i class="bi <?= $n['icon'] ?: 'bi-bell' ?>"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-title text-dark"><?= sanitizeOutput($n['title']) ?></div>
                                            <div class="notification-body text-truncate" style="max-width: 200px;"><?= sanitizeOutput($n['message']) ?></div>
                                            <div class="notification-time"><?= getTimeElapsed($n['created_at']) ?></div>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </ul>
            </div>

            <div class="vr mx-1 bg-secondary opacity-10" style="height: 24px;"></div>

            <!-- User Profile -->
            <div class="dropdown">
                <button class="btn-user-profile" id="userProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar-wrapper">
                        <?php
                        $nav_avatar = getProfileImage($current_student['gender'] ?? 'Male', $student_id, $current_student['avatar_id'] ?? null);
                        ?>
                        <img src="../assets/images/Avatar/<?= $nav_avatar ?>" alt="User" class="user-avatar">
                        <span class="status-indicator online"></span>
                    </div>
                    <div class="user-info d-none d-sm-block">
                        <span class="user-name"><?= sanitizeOutput($_SESSION['name'] ?? 'Student') ?></span>
                        <span class="user-role"><?= sanitizeOutput($_SESSION['roll_number'] ?? 'N/A') ?></span>
                    </div>
                </button>

                <ul class="dropdown-menu dropdown-menu-end profile-dropdown animate slideIn" aria-labelledby="userProfileDropdown">
                    <li class="profile-header">
                        <div class="d-flex align-items-center gap-3">
                            <img src="../assets/images/Avatar/<?= $nav_avatar ?>" alt="User" class="profile-header-avatar">
                            <div>
                                <div class="fw-bold"><?= sanitizeOutput($_SESSION['name'] ?? 'Student') ?></div>
                                <div class="small text-muted"><?= sanitizeOutput($_SESSION['email'] ?? 'student@example.com') ?></div>
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