<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-mortarboard"></i> Staff Panel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#staffNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="staffNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="start_exam.php"><i class="bi bi-play-circle"></i> Start Exam</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="monitor_exam.php"><i class="bi bi-eye"></i> Monitor Exam</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="exam_history.php"><i class="bi bi-clock-history"></i> History</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="evaluate.php"><i class="bi bi-check2-square"></i> Evaluate</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php"><i class="bi bi-file-earmark-bar-graph"></i> Reports</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= sanitizeOutput($_SESSION['name'] ?? 'Staff') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>