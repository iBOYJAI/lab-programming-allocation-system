<?php

/**
 * Process Start Exam - THE ALLOCATION TRIGGER
 * This is where the magic happens - the allocation algorithm is called
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';
require_once '../includes/allocation_algorithm.php';

startSecureSession();
requireRole(['staff'], 'login.php');

$staff_id = $_SESSION['user_id'];

// Get parameters
$subject_id = intval($_GET['subject_id'] ?? 0);
$lab_id = intval($_GET['lab_id'] ?? 0);
$test_type = sanitizeInput($_GET['test_type'] ?? '');
$duration = intval($_GET['duration'] ?? 0);

if (!$subject_id || !$lab_id || !$test_type) {
    redirect('start_exam.php');
}

$page_title = 'Starting Exam...';
include '../includes/header.php';
?>

<?php include '../includes/navbar_staff.php'; ?>

<div class="app-container">
    <?php include '../includes/sidebar_staff.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_staff.php'; ?>

        <div class="main-content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card-notion animate-fade-up">
                            <div class="card-notion-header bg-primary text-white">
                                <h5 class="mb-0 text-white"><i class="bi bi-gear-fill spin me-2"></i> Processing Exam Session</h5>
                            </div>
                            <div class="card-notion-body p-4">
                                <div id="progress">
                                    <div class="alert alert-light border d-flex align-items-center gap-3">
                                        <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                                        <div>Initializing exam session environment...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// EXECUTION STARTS HERE
try {
    // Step 1: Create exam session
    echo '<script>document.getElementById("progress").innerHTML += \'<div class="alert alert-info"><i class="bi bi-1-circle-fill"></i> Creating exam session...</div>\';</script>';
    flush();

    $session_code = generateSessionCode('EXAM');

    $sql = "INSERT INTO exam_sessions (session_code, subject_id, staff_id, lab_id, test_type, duration_minutes, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'Active')";

    if (!dbExecute($sql, [$session_code, $subject_id, $staff_id, $lab_id, $test_type, $duration])) {
        throw new Exception("Failed to create exam session");
    }

    $session_id = dbLastInsertId();

    echo '<script>document.getElementById("progress").innerHTML += \'<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Session created: ' . $session_code . '</div>\';</script>';
    flush();

    // Step 2: Fetch all students for this lab
    echo '<script>document.getElementById("progress").innerHTML += \'<div class="alert alert-info"><i class="bi bi-2-circle-fill"></i> Fetching students...</div>\';</script>';
    flush();

    $students = dbQuery("SELECT id, roll_number, name, system_no FROM students ORDER BY system_no");

    if (empty($students)) {
        throw new Exception("No students found in the system");
    }

    echo '<script>document.getElementById("progress").innerHTML += \'<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Found ' . count($students) . ' students</div>\';</script>';
    flush();

    // Step 3: RUN ALLOCATION ALGORITHM
    echo '<script>document.getElementById("progress").innerHTML += \'<div class="alert alert-warning"><i class="bi bi-3-circle-fill"></i> Running smart allocation algorithm...</div>\';</script>';
    flush();

    $allocation_result = allocateQuestions($session_id, $subject_id, $students);

    if (!$allocation_result['success']) {
        throw new Exception($allocation_result['error']);
    }

    echo '<script>document.getElementById("progress").innerHTML += \'<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Allocation completed! ' . $allocation_result['allocated_count'] . ' students allocated</div>\';</script>';
    flush();

    // Step 4: Verify allocation quality
    echo '<script>document.getElementById("progress").innerHTML += \'<div class="alert alert-info"><i class="bi bi-4-circle-fill"></i> Verifying allocation quality...</div>\';</script>';
    flush();

    $verification = verifyAllocation($session_id);

    if ($verification['collisions_found'] > 0) {
        echo '<script>document.getElementById("progress").innerHTML += \'<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Warning: ' . $verification['collisions_found'] . ' potential collisions detected</div>\';</script>';
    } else {
        echo '<script>document.getElementById("progress").innerHTML += \'<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Verification passed! No collisions detected (Success rate: ' . $verification['success_rate'] . '%)</div>\';</script>';
    }
    flush();

    // Step 5: Log activity
    logActivity($staff_id, 'staff', 'start_exam', "Started exam session: $session_code");

    // Success - redirect to monitoring page
    echo '
    <script>
    setTimeout(function() {
        document.getElementById("progress").innerHTML += \'<div class="alert alert-success"><h5><i class="bi bi-check-circle-fill"></i> Exam Started Successfully!</h5><p>Redirecting to monitoring panel...</p></div>\';
        setTimeout(function() {
            window.location.href = "monitor_exam.php?session_id=' . $session_id . '";
        }, 2000);
    }, 1000);
    </script>
    ';
} catch (Exception $e) {
    error_log("Exam start error: " . $e->getMessage());

    echo '
    <script>
    document.getElementById("progress").innerHTML += \'<div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> Error: ' . addslashes($e->getMessage()) . '</div>\';
    document.getElementById("progress").innerHTML += \'<div class="mt-3"><a href="start_exam.php" class="btn btn-primary">Try Again</a></div>\';
    </script>
    ';
}
?>

<style>
    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .spin {
        display: inline-block;
        animation: spin 2s linear infinite;
    }
</style>

<?php include '../includes/footer.php'; ?>