<?php

/**
 * Global Search - Admin
 * Premium Redesign
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

$query = $_GET['q'] ?? '';
$results = [
    'students' => [],
    'staff' => [],
    'labs' => [],
    'subjects' => []
];

if (!empty($query)) {
    $search = "%$query%";

    // Search Students
    $results['students'] = dbQuery("
        SELECT id, name, roll_number, department, email
        FROM students 
        WHERE name LIKE ? OR roll_number LIKE ? 
        LIMIT 5
    ", [$search, $search]);

    // Search Staff
    $results['staff'] = dbQuery("
        SELECT id, name, staff_id, department, email 
        FROM staff 
        WHERE name LIKE ? OR staff_id LIKE ? 
        LIMIT 5
    ", [$search, $search]);

    // Search Labs
    $results['labs'] = dbQuery("
        SELECT id, lab_name, total_systems as capacity
        FROM labs 
        WHERE lab_name LIKE ? 
        LIMIT 5
    ", [$search]);

    // Search Subjects
    $results['subjects'] = dbQuery("
        SELECT id, subject_name, subject_code 
        FROM subjects 
        WHERE subject_name LIKE ? OR subject_code LIKE ? 
        LIMIT 5
    ", [$search, $search]);
}

$page_title = 'Search Results';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <div class="d-flex align-items-center mb-4 animate-fade-up">
                <div>
                    <h1 class="fw-bold mb-1 text-dark fs-3">Search Results</h1>
                    <p class="text-secondary mb-0">Showing matches for: <span class="fw-bold text-primary">"<?= sanitizeOutput($query) ?>"</span></p>
                </div>
            </div>

            <?php if (empty($results['students']) && empty($results['staff']) && empty($results['labs']) && empty($results['subjects'])): ?>
                <div class="card-notion animate-fade-up delay-100 py-5 text-center">
                    <div class="opacity-25 mb-4">
                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-search.svg" width="80">
                    </div>
                    <h4 class="text-dark fw-bold mb-2">No matches found</h4>
                    <p class="text-muted">We couldn't find anything matching your search term.</p>
                    <a href="dashboard.php" class="btn btn-outline-primary rounded-pill px-4 mt-2">Return to Dashboard</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <!-- Students Results -->
                    <?php if (!empty($results['students'])): ?>
                        <div class="col-md-6">
                            <div class="card-notion h-100 animate-fade-up delay-100">
                                <div class="card-notion-header">
                                    <h5 class="card-notion-title">
                                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-user-square.svg" width="18" class="opacity-50">
                                        Students
                                    </h5>
                                    <span class="badge bg-light text-secondary border"><?= count($results['students']) ?> matches</span>
                                </div>
                                <div class="p-0">
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($results['students'] as $student): ?>
                                            <a href="manage_students.php?search=<?= $student['roll_number'] ?>" class="list-group-item list-group-item-action border-bottom p-3 d-flex align-items-center gap-3">
                                                <div class="flex-shrink-0 bg-light rounded-circle d-flex align-items-center justify-content-center text-primary" style="width: 40px; height: 40px;">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold text-dark mb-0"><?= sanitizeOutput($student['name']) ?></div>
                                                    <div class="small text-muted">
                                                        <?= sanitizeOutput($student['roll_number']) ?> • <?= sanitizeOutput($student['department']) ?>
                                                    </div>
                                                </div>
                                                <i class="bi bi-chevron-right text-muted small"></i>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Staff Results -->
                    <?php if (!empty($results['staff'])): ?>
                        <div class="col-md-6">
                            <div class="card-notion h-100 animate-fade-up delay-100">
                                <div class="card-notion-header">
                                    <h5 class="card-notion-title">
                                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-briefcase.svg" width="18" class="opacity-50">
                                        Staff
                                    </h5>
                                    <span class="badge bg-light text-secondary border"><?= count($results['staff']) ?> matches</span>
                                </div>
                                <div class="p-0">
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($results['staff'] as $staff): ?>
                                            <a href="manage_staff.php?search=<?= $staff['staff_id'] ?>" class="list-group-item list-group-item-action border-bottom p-3 d-flex align-items-center gap-3">
                                                <div class="flex-shrink-0 bg-light rounded-circle d-flex align-items-center justify-content-center text-indigo" style="width: 40px; height: 40px;">
                                                    <i class="bi bi-person-badge"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold text-dark mb-0"><?= sanitizeOutput($staff['name']) ?></div>
                                                    <div class="small text-muted">
                                                        <?= sanitizeOutput($staff['staff_id']) ?> • <?= sanitizeOutput($staff['department']) ?>
                                                    </div>
                                                </div>
                                                <i class="bi bi-chevron-right text-muted small"></i>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Subjects Results -->
                    <?php if (!empty($results['subjects'])): ?>
                        <div class="col-md-6">
                            <div class="card-notion h-100 animate-fade-up delay-200">
                                <div class="card-notion-header">
                                    <h5 class="card-notion-title">
                                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-collection.svg" width="18" class="opacity-50">
                                        Subjects
                                    </h5>
                                    <span class="badge bg-light text-secondary border"><?= count($results['subjects']) ?> matches</span>
                                </div>
                                <div class="p-0">
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($results['subjects'] as $sub): ?>
                                            <a href="manage_subjects.php" class="list-group-item list-group-item-action border-bottom p-3 d-flex align-items-center gap-3">
                                                <div class="flex-shrink-0 bg-light rounded-2 d-flex align-items-center justify-content-center text-danger" style="width: 40px; height: 40px;">
                                                    <i class="bi bi-journal-code"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold text-dark mb-0"><?= sanitizeOutput($sub['subject_name']) ?></div>
                                                    <div class="small text-muted font-monospace">
                                                        <?= sanitizeOutput($sub['subject_code']) ?>
                                                    </div>
                                                </div>
                                                <i class="bi bi-chevron-right text-muted small"></i>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Labs Results -->
                    <?php if (!empty($results['labs'])): ?>
                        <div class="col-md-6">
                            <div class="card-notion h-100 animate-fade-up delay-200">
                                <div class="card-notion-header">
                                    <h5 class="card-notion-title">
                                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-building.svg" width="18" class="opacity-50">
                                        Labs
                                    </h5>
                                    <span class="badge bg-light text-secondary border"><?= count($results['labs']) ?> matches</span>
                                </div>
                                <div class="p-0">
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($results['labs'] as $lab): ?>
                                            <a href="manage_labs.php" class="list-group-item list-group-item-action border-bottom p-3 d-flex align-items-center gap-3">
                                                <div class="flex-shrink-0 bg-light rounded-2 d-flex align-items-center justify-content-center text-success" style="width: 40px; height: 40px;">
                                                    <i class="bi bi-pc-display"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold text-dark mb-0"><?= sanitizeOutput($lab['lab_name']) ?></div>
                                                    <div class="small text-muted">
                                                        <i class="bi bi-geo-alt me-1"></i><?= sanitizeOutput($lab['location']) ?>
                                                    </div>
                                                </div>
                                                <i class="bi bi-chevron-right text-muted small"></i>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>