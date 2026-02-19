<?php

/**
 * Admin Dashboard - Premium Redesign with Charts
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

// Enable Charts in footer
$include_charts = true;

// Get statistics
$sql_stats = "SELECT
    (SELECT COUNT(*) FROM labs) as total_labs,
    (SELECT COUNT(*) FROM staff) as total_staff,
    (SELECT COUNT(*) FROM students) as total_students,
    (SELECT COUNT(*) FROM subjects) as total_subjects,
    (SELECT COUNT(*) FROM questions) as total_questions,
    (SELECT COUNT(*) FROM exam_sessions WHERE status = 'Active') as active_sessions";

$stats = dbQuery($sql_stats)[0];

// Data for Charts
$subject_distribution = dbQuery("
    SELECT s.subject_name, COUNT(es.id) as session_count
    FROM subjects s
    LEFT JOIN exam_sessions es ON s.id = es.subject_id
    GROUP BY s.id, s.subject_name
    ORDER BY session_count DESC
");

$session_status_data = dbQuery("
    SELECT status, COUNT(*) as count 
    FROM exam_sessions 
    GROUP BY status
");

// Get recent activity logs
$recent_activity = dbQuery("
    SELECT * FROM activity_logs 
    ORDER BY created_at DESC 
    LIMIT 6
");

// Get active sessions
$active_sessions_data = dbQuery("
    SELECT es.*, s.subject_name, st.name as staff_name, l.lab_name,
    (SELECT COUNT(*) FROM student_allocations sa WHERE sa.session_id = es.id) as total_students,
    (SELECT COUNT(*) FROM student_allocations sa WHERE sa.session_id = es.id AND (sa.status_q1 = 'Submitted' AND sa.status_q2 = 'Submitted')) as completed_students
    FROM exam_sessions es
    INNER JOIN subjects s ON es.subject_id = s.id
    INNER JOIN staff st ON es.staff_id = st.id
    INNER JOIN labs l ON es.lab_id = l.id
    WHERE es.status = 'Active'
    ORDER BY es.started_at DESC
");

$page_title = 'Enterprise Dashboard';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <!-- Welcome Section -->
            <div class="admin-hero animate-fade-up">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-rocket.svg" width="28" class="opacity-75">
                            <h1 class="fw-bold mb-0 text-dark fs-3">System Control Center</h1>
                        </div>
                        <p class="mb-0 text-muted ps-1">Overview of your campus examination infrastructure. All systems normal.</p>
                    </div>
                    <div class="d-none d-lg-block">
                        <div class="d-flex align-items-center gap-2 bg-light px-3 py-2 rounded-3 border">
                            <div class="spinner-grow text-success spinner-grow-sm" role="status"></div>
                            <span class="small fw-bold text-secondary font-monospace" id="current-time">00:00:00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="row g-4 mb-4">
                <?php
                $stat_items = [
                    ['label' => 'Total Workforce', 'value' => $stats['total_staff'], 'icon' => 'ni-users', 'color' => 'indigo', 'trend' => '+2 this week'],
                    ['label' => 'Total Students', 'value' => $stats['total_students'], 'icon' => 'ni-user-square', 'color' => 'green', 'trend' => 'Stable'],
                    ['label' => 'Question Bank', 'value' => $stats['total_questions'], 'icon' => 'ni-question', 'color' => 'yellow', 'trend' => '+12 added'],
                    ['label' => 'Live Exams', 'value' => $stats['active_sessions'], 'icon' => 'ni-browser', 'color' => 'red', 'trend' => 'Real-time']
                ];
                foreach ($stat_items as $index => $item): ?>
                    <div class="col-xl-3 col-md-6">
                        <div class="notion-stat-card animate-fade-up delay-<?= ($index + 1) * 100 ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="notion-stat-icon-wrapper <?= $item['color'] ?>">
                                    <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/<?= $item['icon'] ?>.svg" width="24">
                                </div>
                                <span class="notion-stat-trend <?= $item['color'] === 'red' ? 'positive' : 'neutral' ?>">
                                    <?= $item['trend'] ?>
                                </span>
                            </div>
                            <div class="mt-auto">
                                <div class="notion-stat-label"><?= $item['label'] ?></div>
                                <div class="notion-stat-value"><?= number_format($item['value']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-4 mb-4">
                <!-- Data Visualizations -->
                <div class="col-lg-6">
                    <div class="card-notion animate-fade-up delay-200">
                        <div class="card-notion-header">
                            <h5 class="card-notion-title">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-doughnut-chart.svg" width="18" class="opacity-50">
                                Subject Distribution
                            </h5>
                        </div>
                        <div class="card-notion-body">
                            <canvas id="subjectChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card-notion animate-fade-up delay-300">
                        <div class="card-notion-header">
                            <h5 class="card-notion-title">
                                <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-timeline.svg" width="18" class="opacity-50">
                                Session Status
                            </h5>
                        </div>
                        <div class="card-notion-body">
                            <canvas id="statusChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Live Monitoring -->
                <div class="col-lg-8">
                    <div class="card-notion animate-fade-up delay-300">
                        <div class="card-notion-header">
                            <div>
                                <h5 class="card-notion-title mb-1">Live Console Tracking</h5>
                                <div class="small text-muted fw-normal">Real-time examination monitoring</div>
                            </div>
                            <span class="notion-badge live">
                                <span class="spinner-grow spinner-grow-sm me-1" style="width: 0.5rem; height: 0.5rem;" role="status"></span> LIVE
                            </span>
                        </div>
                        <div class="p-0">
                            <?php if (empty($active_sessions_data)): ?>
                                <div class="text-center py-5 px-4">
                                    <div class="opacity-25 mb-3">
                                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-laptop-fill.svg" width="64">
                                    </div>
                                    <p class="text-muted mb-3">No sessions are currently active on the network.</p>
                                    <button class="btn btn-primary btn-sm px-4 rounded-pill" onclick="location.href='view_sessions.php'">View All History</button>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="notion-table">
                                        <thead>
                                            <tr>
                                                <th class="ps-4">Examination</th>
                                                <th>Facility</th>
                                                <th>Attendance</th>
                                                <th>Progress</th>
                                                <th class="text-end pe-4">Command</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($active_sessions_data as $session): ?>
                                                <?php
                                                $total = max(1, $session['total_students']);
                                                $pct = round(($session['completed_students'] / $total) * 100);
                                                ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="fw-bold text-dark mb-0"><?= sanitizeOutput($session['subject_name']) ?></div>
                                                        <code class="x-small text-muted bg-light px-2 py-1 rounded"><?= sanitizeOutput($session['session_code']) ?></code>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-truck-location.svg" width="14" class="opacity-50">
                                                            <span class="small fw-bold text-secondary"><?= sanitizeOutput($session['lab_name']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="small fw-bold"><?= $session['completed_students'] ?> <small class="text-muted">/ <?= $session['total_students'] ?></small></div>
                                                    </td>
                                                    <td>
                                                        <div class="progress" style="height: 6px; width: 100px; border-radius: 3px;">
                                                            <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
                                                        </div>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <a href="view_sessions.php?id=<?= $session['id'] ?>" class="btn btn-light btn-sm rounded-3 border"><i class="bi bi-arrow-right"></i></a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Audit Trails -->
                <div class="col-lg-4">
                    <div class="card-notion animate-fade-up delay-300">
                        <div class="card-notion-header">
                            <h5 class="card-notion-title">System Audit Log</h5>
                            <a href="logs.php" class="small text-primary text-decoration-none fw-bold">View History</a>
                        </div>
                        <div class="card-notion-body p-0">
                            <div class="timeline p-4">
                                <?php foreach ($recent_activity as $activity): ?>
                                    <div class="timeline-item d-flex gap-3 mb-4">
                                        <div class="timeline-dot-container position-relative">
                                            <div class="timeline-dot bg-indigo"></div>
                                            <div class="timeline-line"></div>
                                        </div>
                                        <div class="timeline-content overflow-hidden">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small class="notion-badge bg-light text-secondary"><?= sanitizeOutput($activity['user_role']) ?></small>
                                                <small class="text-muted" style="font-size: 0.7rem;"><?= getTimeElapsed($activity['created_at']) ?></small>
                                            </div>
                                            <p class="text-dark small mb-0 text-truncate opacity-80"><?= sanitizeOutput($activity['description']) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Prepare data for JS
$subject_labels = array_column($subject_distribution, 'subject_name');
$subject_counts = array_column($subject_distribution, 'session_count');
$status_labels = array_column($session_status_data, 'status');
$status_counts = array_column($session_status_data, 'count');

$extra_js = "
<script>
    // Prepare Charts
    const ctxSubject = document.getElementById('subjectChart').getContext('2d');
    new Chart(ctxSubject, {
        type: 'bar',
        data: {
            labels: " . json_encode($subject_labels) . ",
            datasets: [{
                label: 'Sessions',
                data: " . json_encode($subject_counts) . ",
                backgroundColor: '#6366F1', // Indigo
                borderRadius: 6,
                barThickness: 24
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { grid: { display: false }, ticks: { font: { family: 'Inter' } } }, 
                x: { grid: { color: '#F3F4F6' }, ticks: { font: { family: 'Inter' } } } 
            }
        }
    });

    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: " . json_encode($status_labels) . ",
            datasets: [{
                data: " . json_encode($status_counts) . ",
                backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#6366F1'], // Green, Yellow, Red, Indigo
                hoverOffset: 10,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { 
                    position: 'bottom',
                    labels: { usePointStyle: true, boxWidth: 8, padding: 20, font: { family: 'Inter', size: 12 } }
                } 
            },
            cutout: '75%',
            layout: { padding: 10 }
        }
    });

    // clock logic
    function updateClock() {
        const now = new Date();
        document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US');
    }
    setInterval(updateClock, 1000);
    updateClock();
</script>
";
?>

<style>
    .timeline-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        box-shadow: 0 0 0 3px #E0E7FF;
    }

    .timeline-line {
        position: absolute;
        top: 10px;
        left: 4.5px;
        width: 1px;
        height: calc(100% + 1.5rem);
        background: #F3F4F6;
    }

    .timeline-item:last-child .timeline-line {
        display: none;
    }

    .chart-container {
        position: relative;
    }
</style>

<?php include '../includes/footer.php'; ?>