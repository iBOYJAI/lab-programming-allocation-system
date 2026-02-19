<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <div class="brand-wrapper">
            <div class="brand-icon">
                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-briefcase.svg" alt="Logo" class="notion-icon-white" width="24">
            </div>
            <div class="brand-info">
                <h4 class="brand-title">LAB ALLOCATE</h4>
                <p class="brand-subtitle">Admin Workspace</p>
            </div>
        </div>
    </div>

    <div class="sidebar-content custom-scrollbar">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <div class="nav-label">Main</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-kanban.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="profile.php" class="nav-link <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-user.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">My Profile</span>
                </a>
            </li>
        </ul>

        <div class="nav-label">Management</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="manage_staff.php" class="nav-link <?= in_array($current_page, ['manage_staff.php', 'view_staff.php']) ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-users.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Staff Members</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_students.php" class="nav-link <?= in_array($current_page, ['manage_students.php', 'view_student.php']) ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-user-square.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Students</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_labs.php" class="nav-link <?= $current_page == 'manage_labs.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-server.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Lab Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_subjects.php" class="nav-link <?= in_array($current_page, ['manage_subjects.php', 'view_subject.php']) ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-file-text.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Subjects</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_questions.php" class="nav-link <?= in_array($current_page, ['manage_questions.php', 'view_question.php']) ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-question.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Question Bank</span>
                </a>
            </li>
        </ul>

        <div class="nav-label">System</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="view_sessions.php" class="nav-link <?= $current_page == 'view_sessions.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-timeline.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Exam Sessions</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="exam_results.php" class="nav-link <?= in_array($current_page, ['exam_results.php', 'view_result_details.php']) ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-clipboard-bar-chart.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Exam Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="logs.php" class="nav-link <?= $current_page == 'logs.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-clipboard-list-check.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Activity Logs</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="notifications.php" class="nav-link <?= $current_page == 'notifications.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-bell.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Notifications</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?= $current_page == 'settings.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-settings.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Settings</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="user-card-mini">
            <a href="logout.php" class="btn-logout-new" data-bs-toggle="tooltip" data-bs-placement="top" title="Sign Out">
                <img src="../assets/images/Notion-Resources/Notion-Icons/Accent-Color/svg/ni-power-off.svg" class="notion-icon-red" width="18">
                <span>Logout</span>
            </a>
        </div>
    </div>
</aside>

<style>
    .notion-icon {
        opacity: 0.8;
        transition: var(--transition);
        vertical-align: middle;
    }

    .nav-link:hover .notion-icon,
    .nav-link.active .notion-icon {
        opacity: 1;
    }
</style>