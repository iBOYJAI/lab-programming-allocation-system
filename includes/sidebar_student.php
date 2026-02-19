<aside class="sidebar" id="mainSidebar">
    <div class="sidebar-header">
        <div class="brand-wrapper">
            <div class="brand-icon">
                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-briefcase.svg" alt="Logo" class="notion-icon-white" width="24">
            </div>
            <div class="brand-info">
                <h4 class="brand-title">LAB ALLOCATE</h4>
                <p class="brand-subtitle">Student Portal</p>
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

        <div class="nav-label">Academics</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="exam.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'exam.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-pencil.svg" width="20" class="notion-icon"></span>
                    <span class="nav-text">Active Exam</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="history.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'history.php' ? 'active' : '' ?>">
                    <span class="nav-icon"><img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-timeline.svg" width="20" class="notion-icon"></span>
                    <span class="nav-text">Exam History</span>
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