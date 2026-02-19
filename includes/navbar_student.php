<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="exam.php">
            <i class="bi bi-code-square"></i> Lab Exam System
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="studentNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="exam.php"><i class="bi bi-pencil-square"></i> Active Exam</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <span class="navbar-text text-white me-3">
                        <i class="bi bi-person-badge"></i> <?= sanitizeOutput($_SESSION['roll_number'] ?? '') ?> - <?= sanitizeOutput($_SESSION['name'] ?? 'Student') ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>