<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-shield-check"></i> Admin Panel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="manageDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-gear"></i> Manage
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="manage_labs.php"><i class="bi bi-building"></i> Labs</a></li>
                        <li><a class="dropdown-item" href="manage_staff.php"><i class="bi bi-people"></i> Staff</a></li>
                        <li><a class="dropdown-item" href="manage_students.php"><i class="bi bi-person"></i> Students</a></li>
                        <li><a class="dropdown-item" href="manage_subjects.php"><i class="bi bi-book"></i> Subjects</a></li>
                        <li><a class="dropdown-item" href="manage_questions.php"><i class="bi bi-question-circle"></i> Questions</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_sessions.php"><i class="bi bi-calendar-event"></i> Sessions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="exam_results.php"><i class="bi bi-file-earmark-bar-graph"></i> Reports</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php"><i class="bi bi-sliders"></i> Settings</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= sanitizeOutput($_SESSION['username'] ?? 'Admin') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>