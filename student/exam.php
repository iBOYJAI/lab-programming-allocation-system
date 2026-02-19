<?php

/**
 * Student Exam Interface - CRITICAL PAGE
 * Shows allocated 2 questions with code editors
 */

require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['student'], 'login.php');

$student_id = $_SESSION['user_id'];

// Get active session for this student
$active_session = getActiveSessionForStudent($student_id);

if (!$active_session) {
    $no_exam = true;
} else {
    $no_exam = false;

    // Get allocated questions details
    $sql = "SELECT sa.*, 
                   q1.id as q1_id, q1.question_title as q1_title, q1.question_text as q1_desc,
                   q1.difficulty as q1_difficulty, q1.sample_input as q1_input, q1.sample_output as q1_output,
                   q2.id as q2_id, q2.question_title as q2_title, q2.question_text as q2_desc,
                   q2.difficulty as q2_difficulty, q2.sample_input as q2_input, q2.sample_output as q2_output,
                   (SELECT submitted_code FROM submissions WHERE allocation_id = sa.id AND question_id = sa.question_1_id LIMIT 1) as submitted_code_q1,
                   (SELECT submitted_code FROM submissions WHERE allocation_id = sa.id AND question_id = sa.question_2_id LIMIT 1) as submitted_code_q2,
                   (SELECT COUNT(*) FROM submissions WHERE allocation_id = sa.id AND question_id = sa.question_1_id) as q1_submitted,
                   (SELECT COUNT(*) FROM submissions WHERE allocation_id = sa.id AND question_id = sa.question_2_id) as q2_submitted
            FROM student_allocations sa
            INNER JOIN questions q1 ON sa.question_1_id = q1.id
            INNER JOIN questions q2 ON sa.question_2_id = q2.id
            WHERE sa.session_id = ? AND sa.student_id = ?";

    $allocation = dbQuery($sql, [$active_session['id'], $student_id])[0] ?? null;

    if (!$allocation) {
        $no_exam = true;
    } elseif ($allocation['is_completed']) {
        $_SESSION['info'] = 'You have already completed or left this exam session.';
        redirect('dashboard.php');
    }
}

$page_title = 'Programming Exam';
$include_codemirror = true;
include '../includes/header.php';
?>

<div class="app-container p-0">
    <div class="main-wrapper w-100 ms-0">
        <div class="main-content p-0">
            <?php if ($no_exam): ?>
                <div class="d-flex flex-column align-items-center justify-content-center py-5 animate-fade-up">
                    <div class="bg-light p-4 rounded-circle mb-4 shadow-sm">
                        <img src="../assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-calendar-square.svg" width="64" class="opacity-25">
                    </div>
                    <h3 class="fw-bold text-dark">No Active Exam Session</h3>
                    <p class="text-secondary text-center mb-4" style="max-width: 400px;">
                        There is currently no active exam scheduled for your account. Please check back later or contact your instructor.
                    </p>
                    <button onclick="location.reload()" class="btn btn-primary rounded-pill px-5 shadow-sm">
                        <i class="bi bi-arrow-clockwise me-2"></i> Refresh System
                    </button>
                </div>
            <?php else: ?>
                <!-- Start Exam Overlay -->
                <div id="exam-overlay" class="position-fixed top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center bg-dark text-white" style="z-index: 99999;">
                    <div class="text-center animate-fade-up">
                        <div class="mb-4">
                            <i class="bi bi-shield-lock display-1 text-primary"></i>
                        </div>
                        <h2 class="fw-bold mb-3">Security Protocol Active</h2>
                        <ul class="text-start mb-4 small text-secondary">
                            <li><i class="bi bi-check2-circle me-2 text-success"></i>Full-screen mode will be enforced.</li>
                            <li><i class="bi bi-check2-circle me-2 text-success"></i>Navigation to other pages is restricted.</li>
                            <li><i class="bi bi-check2-circle me-2 text-success"></i>Copy-paste and Right-click are disabled.</li>
                            <li><i class="bi bi-check2-circle me-2 text-success"></i>Switching tabs may result in disqualification.</li>
                        </ul>
                        <button onclick="startSecureExam()" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-lg">
                            <i class="bi bi-play-fill me-2"></i> Enter Secure Exam Environment
                        </button>
                    </div>
                </div>

                <div id="exam-content" style="display: none;">
                    <div class="container-fluid py-4 px-5">
                        <div class="d-flex justify-content-between align-items-center mb-4 animate-fade-up">
                            <div>
                                <h1 class="fw-bold mb-1 text-dark fs-3"><?= sanitizeOutput($active_session['subject_name']) ?> Exam</h1>
                                <p class="text-secondary mb-0">
                                    <span class="badge bg-blue-subtle text-dark px-3 rounded-pill me-2"><?= sanitizeOutput($active_session['language']) ?> Programming</span>
                                    System #<?= sanitizeOutput($allocation['system_no']) ?>
                                </p>
                            </div>

                            <?php if ($active_session['duration_minutes'] > 0): ?>
                                <div class="d-flex align-items-center gap-3">
                                    <?php
                                    $duration_seconds = $active_session['duration_minutes'] * 60;
                                    $startTime = strtotime($active_session['started_at']);
                                    $endTime = $startTime + $duration_seconds;
                                    $remaining_seconds = $endTime - time();
                                    if ($remaining_seconds < 0) $remaining_seconds = 0;
                                    ?>
                                    <button type="button" class="btn btn-danger rounded-pill px-4 shadow-sm fw-bold h-100 py-3" data-bs-toggle="modal" data-bs-target="#finishExamModal">
                                        <i class="bi bi-box-arrow-right me-2"></i> Finish Exam
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php echo displayAlert(); ?>

                        <div class="row g-4">
                            <!-- Question 1 Panel -->
                            <div class="col-lg-6">
                                <div class="card-notion h-100 animate-fade-up delay-100 overflow-hidden border-0 shadow-sm">
                                    <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                                        <h6 class="fw-bold mb-0 text-dark">
                                            <span class="avatar-circle-sm bg-blue-subtle text-blue me-2">1</span>
                                            Programming Challenge #1
                                        </h6>
                                        <div class="d-flex gap-2 align-items-center">
                                            <?php if ($allocation['q1_submitted']): ?>
                                                <span class="badge bg-green-subtle text-green px-3 rounded-pill">SUBMITTED</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-notion-body p-4">
                                        <div class="question-container mb-4">
                                            <h5 class="fw-bold text-dark mb-3"><?= sanitizeOutput($allocation['q1_title']) ?></h5>
                                            <div class="instruction-box bg-light p-3 rounded-3 border-start border-4 border-blue mb-4">
                                                <?= nl2br(sanitizeOutput($allocation['q1_desc'])) ?>
                                            </div>
                                        </div>

                                        <div class="editor-wrapper mb-4 animate-fade-up">
                                            <div class="editor-header d-flex justify-content-between align-items-center p-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="window-dots d-flex gap-1 me-2">
                                                        <span class="dot-red"></span>
                                                        <span class="dot-yellow"></span>
                                                        <span class="dot-green"></span>
                                                    </div>
                                                    <span class="badge bg-white-10 text-white-50 x-small fw-bold px-2 py-1 rounded">
                                                        <i class="bi bi-code-square me-1"></i> <?= strtoupper($active_session['language']) ?>
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center gap-3">
                                                    <button type="button" class="btn-editor-tool" onclick="clearEditor(1)" title="Clear Code">
                                                        <i class="bi bi-eraser-fill"></i>
                                                    </button>
                                                    <button type="button" class="btn-editor-tool" onclick="toggleFullscreen('editor-q1')" title="Fullscreen">
                                                        <i class="bi bi-arrows-fullscreen"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <textarea id="editor-q1" name="code-q1"><?= sanitizeOutput($allocation['submitted_code_q1'] ?? $allocation['draft_code_q1'] ?? '') ?></textarea>
                                        </div>

                                        <div class="d-grid shadow-sm">
                                            <button onclick="submitCode(1, <?= $allocation['q1_id'] ?>, <?= $allocation['id'] ?>)"
                                                class="btn <?= $allocation['q1_submitted'] ? 'btn-success' : 'btn-primary' ?> rounded-pill py-2 fw-bold w-100"
                                                id="submit-q1"
                                                data-submitted="<?= $allocation['q1_submitted'] ? '1' : '0' ?>">
                                                <i class="bi <?= $allocation['q1_submitted'] ? 'bi-check-circle-fill' : 'bi-cloud-arrow-up' ?> me-2"></i>
                                                <?= $allocation['q1_submitted'] ? 'Update Submission' : 'Submit Challenge 1' ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Question 2 Panel -->
                            <div class="col-lg-6">
                                <div class="card-notion h-100 animate-fade-up delay-200 overflow-hidden border-0 shadow-sm">
                                    <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                                        <h6 class="fw-bold mb-0 text-dark">
                                            <span class="avatar-circle-sm bg-purple-subtle text-purple me-2">2</span>
                                            Programming Challenge #2
                                        </h6>
                                        <div class="d-flex gap-2 align-items-center">
                                            <?php if ($allocation['q2_submitted']): ?>
                                                <span class="badge bg-green-subtle text-green px-3 rounded-pill">SUBMITTED</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-notion-body p-4">
                                        <div class="question-container mb-4">
                                            <h5 class="fw-bold text-dark mb-3"><?= sanitizeOutput($allocation['q2_title']) ?></h5>
                                            <div class="instruction-box bg-light p-3 rounded-3 border-start border-4 border-purple mb-4">
                                                <?= nl2br(sanitizeOutput($allocation['q2_desc'])) ?>
                                            </div>
                                        </div>

                                        <div class="editor-wrapper mb-4 animate-fade-up">
                                            <div class="editor-header d-flex justify-content-between align-items-center p-3">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="window-dots d-flex gap-1 me-2">
                                                        <span class="dot-red"></span>
                                                        <span class="dot-yellow"></span>
                                                        <span class="dot-green"></span>
                                                    </div>
                                                    <span class="badge bg-white-10 text-white-50 x-small fw-bold px-2 py-1 rounded">
                                                        <i class="bi bi-code-square me-1"></i> <?= strtoupper($active_session['language']) ?>
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center gap-3">
                                                    <button type="button" class="btn-editor-tool" onclick="clearEditor(2)" title="Clear Code">
                                                        <i class="bi bi-eraser-fill"></i>
                                                    </button>
                                                    <button type="button" class="btn-editor-tool" onclick="toggleFullscreen('editor-q2')" title="Fullscreen">
                                                        <i class="bi bi-arrows-fullscreen"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <textarea id="editor-q2" name="code-q2"><?= sanitizeOutput($allocation['submitted_code_q2'] ?? $allocation['draft_code_q2'] ?? '') ?></textarea>
                                        </div>

                                        <div class="d-grid shadow-sm">
                                            <button onclick="submitCode(2, <?= $allocation['q2_id'] ?>, <?= $allocation['id'] ?>)"
                                                class="btn <?= $allocation['q2_submitted'] ? 'btn-success' : 'btn-primary' ?> rounded-pill py-2 fw-bold w-100"
                                                id="submit-q2"
                                                data-submitted="<?= $allocation['q2_submitted'] ? '1' : '0' ?>">
                                                <i class="bi <?= $allocation['q2_submitted'] ? 'bi-check-circle-fill' : 'bi-cloud-arrow-up' ?> me-2"></i>
                                                <?= $allocation['q2_submitted'] ? 'Update Submission' : 'Submit Challenge 2' ?>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Finish Exam Modal -->
                        <div class="modal fade" id="finishExamModal" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg rounded-4">
                                    <div class="modal-body text-center p-5">
                                        <div class="mb-4 text-danger">
                                            <i class="bi bi-exclamation-triangle display-1"></i>
                                        </div>
                                        <h3 class="fw-bold text-dark mb-3">Terminate Exam Session?</h3>
                                        <p class="text-secondary mb-4">
                                            Are you sure you want to finish the exam? This will lock all your submissions and you will <strong>not be able to re-enter</strong> the session.
                                        </p>
                                        <form action="finish_exam.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="allocation_id" value="<?= $allocation['id'] ?>">
                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-danger py-3 rounded-pill fw-bold">
                                                    Yes, Finish and Exit
                                                </button>
                                                <button type="button" class="btn btn-light py-3 rounded-pill fw-bold text-secondary" data-bs-dismiss="modal">
                                                    Continue Working
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> <!-- container -->

                    <input type="hidden" id="remaining_seconds" value="<?= $remaining_seconds ?>">
                    <div class="timer-pill" id="timer-container">
                        <i class="bi bi-clock-fill text-primary" id="timer-icon"></i>
                        <div class="d-flex flex-column">
                            <span class="text-secondary" style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Time Remaining</span>
                            <div id="time" class="time-val">--:--</div>
                        </div>
                    </div>
                </div> <!-- exam-content -->
            <?php endif; ?>
        </div>
    </div>

    <style>
        .timer-card {
            background: #fff;
            border-radius: 12px;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border: 1px solid #eee;
        }

        .timer-icon {
            width: 40px;
            height: 40px;
            background: #fff0f0;
            color: #ef4444;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .timer-label {
            font-size: 0.65rem;
            font-weight: 800;
            color: #94a3b8;
            letter-spacing: 0.05em;
        }

        .timer-value {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 1.15rem;
            color: #1e293b;
            line-height: 1;
        }

        .editor-wrapper {
            background: #1e1e1e;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid #2d2d2d;
            position: relative;
        }

        .editor-header {
            background: #252526;
            border-bottom: 1px solid #333333;
            z-index: 10;
        }

        /* Only hide the original textarea, use IDs to be safe */
        #editor-q1,
        #editor-q2 {
            display: none !important;
        }

        .window-dots span {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot-red {
            background: #ff5f56;
        }

        .dot-yellow {
            background: #ffbd2e;
        }

        .dot-green {
            background: #27c93f;
        }

        .bg-white-10 {
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-editor-tool {
            background: none;
            border: none;
            color: #858585;
            font-size: 1.1rem;
            padding: 0;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-editor-tool:hover {
            color: #ffffff;
            transform: scale(1.1);
        }

        .CodeMirror {
            height: 450px !important;
            width: 100% !important;
            font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', monospace !important;
            font-size: 15px !important;
            line-height: 1.6 !important;
            z-index: 50 !important;
        }

        .CodeMirror-fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            height: auto !important;
            z-index: 9999;
        }

        .CodeMirror-gutters {
            background: #1e1e1e !important;
            border-right: 1px solid #333 !important;
        }

        .CodeMirror-linenumber {
            color: #6e7681 !important;
        }

        /* Fullscreen lockdown */
        body.exam-mode {
            overflow: auto !important;
        }

        .main-content {
            min-height: 100vh;
            background: #f8fafc;
        }

        /* Floating Timer */
        .timer-pill {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 12px 24px;
            border-radius: 99px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .timer-pill.low-time {
            background: #fee2e2;
            border-color: #ef4444;
            animation: pulse-red 2s infinite;
        }

        .timer-pill .time-val {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.2rem;
            font-weight: 800;
            color: #1e293b;
        }

        .timer-pill.low-time .time-val {
            color: #b91c1c;
        }

        @keyframes pulse-red {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }
    </style>

    <script>
        <?php if (!$no_exam): ?>
            // Global variables (using window.editor1 and window.editor2)

            // Define functions globally at the top
            window.startSecureExam = function() {
                try {
                    const docEl = document.documentElement;
                    const requestFs = docEl.requestFullscreen || docEl.webkitRequestFullscreen || docEl.mozRequestFullScreen || docEl.msRequestFullscreen;

                    if (requestFs) {
                        requestFs.call(docEl).catch(e => {
                            console.warn("Fullscreen request failed, but proceeding to exam:", e);
                        });
                    }
                } catch (err) {
                    console.error("Fullscreen error:", err);
                }

                // Hide overlay and show exam contents regardless of fullscreen success
                const overlay = document.getElementById('exam-overlay');
                const content = document.getElementById('exam-content');

                if (overlay) {
                    overlay.style.setProperty('display', 'none', 'important');
                }
                if (content) {
                    content.style.display = 'block';
                }

                document.body.classList.add('exam-mode');

                // Update status to 'In Progress' via AJAX
                $.ajax({
                    url: 'update_exam_progress.php',
                    method: 'POST',
                    dataType: 'json'
                });

                // Refresh editors and attachment with multiple delays for bulletproof rendering
                if (window.editor1 && window.editor2) {
                    const refreshes = [50, 200, 500, 1000, 2000, 5000];
                    refreshes.forEach(delay => {
                        setTimeout(() => {
                            window.editor1.refresh();
                            window.editor2.refresh();
                            if (delay === 500) {
                                window.editor1.focus();
                                console.log("Focusing Editor 1...");
                            }
                            console.log(`Editors refreshed at ${delay}ms`);
                        }, delay);
                    });
                }

                // Block all copy/paste/context in CodeMirror editors explicitly
                if (window.editor1 && window.editor2) {
                    [window.editor1, window.editor2].forEach(editor => {
                        editor.on('copy', (cm, e) => e.preventDefault());
                        editor.on('cut', (cm, e) => e.preventDefault());
                        editor.on('paste', (cm, e) => {
                            e.preventDefault();
                            showToast('Pasting is disabled!', 'warning');
                        });
                    });
                }
            };

            window.clearEditor = function(num) {
                if (confirm('Are you sure you want to clear all code in this editor? This cannot be undone.')) {
                    const editor = num === 1 ? window.editor1 : window.editor2;
                    editor.setValue('');
                    showToast('Editor cleared', 'info');
                }
            };

            window.toggleFullscreen = function(editorId) {
                const editor = editorId === 'editor-q1' ? window.editor1 : window.editor2;
                if (editor) {
                    const wrapper = editor.getWrapperElement();
                    if (!document.fullscreenElement) {
                        wrapper.requestFullscreen().catch(err => {
                            showToast(`Error: ${err.message}`, 'error');
                        });
                    } else {
                        document.exitFullscreen();
                    }
                }
            };

            window.submitCode = function(questionNum, questionId, allocationId, isAuto = false) {
                const editor = questionNum === 1 ? window.editor1 : window.editor2;
                const code = editor.getValue();

                if (!isAuto && !code.trim()) {
                    showToast('Please write some code before submitting', 'warning');
                    return;
                }

                let submissionKey = '';
                const btn = document.getElementById('submit-q' + questionNum);
                const isRework = btn.getAttribute('data-submitted') === '1';

                if (!isAuto) {
                    if (isRework) {
                        submissionKey = prompt(`CONFIRM REWORK (Question ${questionNum})\n\nYou have already submitted this challenge once.\n\nEnter the current 6-digit SUBMISSION KEY from the staff dashboard to overwrite your previous submission:`);
                        if (submissionKey === null) return; // Cancelled
                        if (submissionKey.trim().length !== 6) {
                            showToast('Invalid key format. Please enter the 6-digit code shown on the monitor.', 'danger');
                            return;
                        }
                    } else if (!confirm(`Submit your code for Question ${questionNum}?`)) {
                        return;
                    }
                }

                if (!isAuto) showLoading('Submitting code...');

                $.ajax({
                    url: 'submit_code.php',
                    method: 'POST',
                    data: {
                        question_id: questionId,
                        allocation_id: allocationId,
                        code: code,
                        submission_key: submissionKey,
                        csrf_token: '<?= generateCSRFToken() ?>',
                        is_auto: isAuto ? 1 : 0
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (!isAuto) hideLoading();
                        if (response.success) {
                            if (!isAuto) showToast(response.is_rework ? 'Submission updated successfully!' : 'Code submitted successfully!', 'success');

                            // Update button appearance
                            btn.setAttribute('data-submitted', '1');
                            btn.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> Update Submission';
                            btn.className = 'btn btn-success rounded-pill py-2 fw-bold w-100';

                            // Keep the editor unlocked for reworks
                        } else {
                            if (!isAuto) showToast('Error: ' + response.error, 'error');
                        }
                    },
                    error: function() {
                        if (!isAuto) hideLoading();
                        if (!isAuto) showToast('Submission failed. Please try again.', 'error');
                    }
                });
            };

            window.autoSubmitExam = function(message = 'Automatically submitting your work...') {
                showLoading(message);

                // Submit Q1 if exists and NOT yet submitted manually if needed, 
                // but autoSubmitExam usually just force submits current state.
                const q1Btn = document.getElementById('submit-q1');
                if (q1Btn) {
                    submitCode(1, <?= $allocation['q1_id'] ?>, <?= $allocation['id'] ?>, true);
                }

                // Submit Q2 if exists
                const q2Btn = document.getElementById('submit-q2');
                if (q2Btn) {
                    submitCode(2, <?= $allocation['q2_id'] ?>, <?= $allocation['id'] ?>, true);
                }

                setTimeout(function() {
                    location.href = 'dashboard.php?msg=exam_ended';
                }, 2000);
            };

            // Main initialization
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize CodeMirror editors
                const editorMode = '<?= getCodeMirrorMode(ucfirst(strtolower($active_session['language']))) ?>';
                console.log("Initializing Editor 1 with mode:", editorMode);

                const editorOptions = {
                    mode: editorMode,
                    theme: 'monokai',
                    lineNumbers: true,
                    indentUnit: 4,
                    lineWrapping: true,
                    inputStyle: 'contenteditable',
                    readOnly: false,
                    extraKeys: {
                        "F11": function(cm) {
                            cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                        },
                        "Esc": function(cm) {
                            if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
                        }
                    }
                };

                const options1 = Object.assign({}, editorOptions, {
                    readOnly: false,
                    theme: 'monokai'
                });
                const options2 = Object.assign({}, editorOptions, {
                    readOnly: false,
                    theme: 'monokai'
                });

                window.editor1 = CodeMirror.fromTextArea(document.getElementById('editor-q1'), options1);
                window.editor2 = CodeMirror.fromTextArea(document.getElementById('editor-q2'), options2);

                window.editor1.setSize("100%", 450);
                window.editor2.setSize("100%", 450);

                // Timer Logic
                const remainingInput = document.getElementById('remaining_seconds');
                if (remainingInput) {
                    let timeLeft = parseInt(remainingInput.value);
                    const display = document.getElementById('time');
                    const timerContainer = document.getElementById('timer-container');
                    const timerIcon = document.getElementById('timer-icon');

                    const timerInterval = setInterval(function() {
                        if (timeLeft <= 0) {
                            clearInterval(timerInterval);
                            display.textContent = "00:00";
                            autoSubmitExam('Time up! Automatically submitting your work...');
                        } else {
                            const minutes = Math.floor(timeLeft / 60);
                            const seconds = timeLeft % 60;
                            display.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

                            // Warning at 5 minutes
                            if (timeLeft <= 300) {
                                timerContainer.classList.add('low-time');
                                if (timerIcon) timerIcon.className = 'bi bi-exclamation-triangle-fill text-danger';
                            }

                            timeLeft--;
                        }
                    }, 1000);
                }

                // Session status polling (Heartbeat)
                setInterval(function() {
                    $.ajax({
                        url: 'heartbeat.php',
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.status !== 'Active') {
                                autoSubmitExam('The exam has been ended by the instructor.');
                            }
                        }
                    });
                }, 10000);

                // Activate anti-cheat measures (from custom.js)
                if (typeof AntiCheat !== 'undefined') {
                    AntiCheat.init();
                }
            });

            // Re-enforce fullscreen if exited
            document.addEventListener('fullscreenchange', function() {
                const overlay = document.getElementById('exam-overlay');
                if (!document.fullscreenElement && overlay && overlay.style.display === 'none') {
                    showToast('Warning: You must stay in full-screen mode!', 'danger');
                }
            });
        <?php endif; ?>
    </script>

    <?php include '../includes/footer.php'; ?>