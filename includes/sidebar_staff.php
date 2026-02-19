<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <div class="brand-wrapper">
            <div class="brand-icon">
                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-briefcase.svg" alt="Logo" class="notion-icon-white" width="24">
            </div>
            <div class="brand-info">
                <h4 class="brand-title">LAB ALLOCATE</h4>
                <p class="brand-subtitle">Staff Workspace</p>
            </div>
        </div>
    </div>

    <div class="sidebar-content custom-scrollbar">
        <div class="nav-label">Main</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-gauge.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-user.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">My Profile</span>
                </a>
            </li>
        </ul>

        <div class="nav-label">Exam Management</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="start_exam.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'start_exam.php' ? 'active' : '' ?>">
                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-arrow-right-circle.svg" width="20" class="notion-icon">
                    <span class="nav-text">Start Exam</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="monitor_exam.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'monitor_exam.php' ? 'active' : '' ?>">
                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-laptop-fill.svg" width="20" class="notion-icon">
                    <span class="nav-text">Monitor Exam</span>
                </a>
            </li>
            <!-- <li class="nav-item">
                <a href="exam_history.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'exam_history.php' ? 'active' : '' ?>">
                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-calendar-square.svg" width="20" class="notion-icon">
                    <span class="nav-text">History</span>
                </a>
            </li> -->
            <li class="nav-item">
                <a href="evaluate.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'evaluate.php' ? 'active' : '' ?>">
                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-list-check-square.svg" width="20" class="notion-icon">
                    <span class="nav-text">Evaluate</span>
                </a>
            </li>
        </ul>

        <div class="nav-label">Records</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="exam_history.php" class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'exam_history.php') ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-calendar-square.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Exam History</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-file-text.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Reports</span>
                </a>
            </li>
        </ul>

        <div class="nav-label">Data Management</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="manage_subjects.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_subjects.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-collection.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">My Subjects</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_questions.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_questions.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-code-square.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Question Bank</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_students.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_students.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-users.svg" class="notion-icon" width="20"></span>
                    <span class="nav-text">Students</span>
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