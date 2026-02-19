<?php

/**
 * System Settings - Premium Management Console
 */
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole(['admin'], '../auth.php');

$page_title = 'System Control';
include '../includes/header.php';
?>

<div class="app-container">
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-wrapper">
        <?php include '../includes/topbar_admin.php'; ?>

        <div class="main-content">
            <div class="row align-items-end mb-4">
                <div class="col">
                    <h1 class="page-title mb-1 text-dark">System Control</h1>
                    <p class="text-muted small mb-0">Manage infrastructure, security parameters, and deployment environment.</p>
                </div>
                <div class="col-auto">
                    <span class="badge notion-badge live d-inline-flex align-items-center gap-2 px-3 py-2">
                        <i class="bi bi-shield-check-fill text-success"></i> System Secure
                    </span>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card card-notion h-100 overflow-hidden">
                        <div class="p-4 border-bottom bg-light bg-opacity-50">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-white p-2 rounded shadow-sm text-indigo">
                                    <i class="bi bi-database-fill fs-5"></i>
                                </div>
                                <h6 class="fw-bold mb-0 text-dark">Database Node</h6>
                            </div>
                        </div>
                        <div class="card-notion-body p-4">
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Host Address</span>
                                    <span class="badge bg-light text-dark border px-3 font-monospace"><?= DB_HOST ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Schema Name</span>
                                    <code class="text-indigo fw-bold bg-indigo-subtle px-2 py-1 rounded"><?= DB_NAME ?></code>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Active Charset</span>
                                    <span class="text-dark small fw-semibold"><?= DB_CHARSET ?></span>
                                </div>
                                <div class="mt-2 pt-3 border-top d-flex align-items-center justify-content-between">
                                    <span class="text-success small fw-bold"><i class="bi bi-check-circle-fill me-1"></i> Stable</span>
                                    <button class="btn btn-sm btn-white border shadow-sm text-indigo fw-bold px-3">Test</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-notion h-100 overflow-hidden">
                        <div class="p-4 border-bottom bg-light bg-opacity-50">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-white p-2 rounded shadow-sm text-dark">
                                    <i class="bi bi-shield-lock-fill fs-5"></i>
                                </div>
                                <h6 class="fw-bold mb-0 text-dark">Security Layer</h6>
                            </div>
                        </div>
                        <div class="card-notion-body p-4">
                            <div class="d-flex flex-column gap-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Session TTL</span>
                                    <span class="text-dark fw-bold"><?= round(SESSION_TIMEOUT / 60) ?> Mins</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">CSRF Protection</span>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2">ENABLED</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted small fw-bold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Timezone</span>
                                    <span class="text-dark small fw-semibold"><?= date_default_timezone_get() ?></span>
                                </div>
                                <div class="mt-2 pt-3 border-top d-flex align-items-center justify-content-between">
                                    <span class="text-muted x-small fst-italic">Auto-rotate</span>
                                    <button class="btn btn-sm btn-white border shadow-sm text-danger fw-bold px-3">Purge</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card card-notion h-100 overflow-hidden">
                        <div class="card-notion-body p-4 text-center d-flex flex-column justify-content-center align-items-center h-100">
                            <div class="status-orb bg-success shadow-success mb-3" style="width: 50px; height: 50px;">
                                <i class="bi bi-check-lg text-white fs-3"></i>
                            </div>
                            <h5 class="fw-bold mb-1 text-dark">System Healthy</h5>
                            <p class="text-muted small mb-3">All nodes operational.</p>

                            <div class="d-flex gap-2 w-100 justify-content-center">
                                <span class="badge bg-light text-success border px-2 py-1"><i class="bi bi-cpu me-1"></i> CPU OK</span>
                                <span class="badge bg-light text-success border px-2 py-1"><i class="bi bi-memory me-1"></i> RAM OK</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Full Width Table Row -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-notion overflow-hidden mb-4">
                        <div class="card-notion-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-0">
                                    <i class="bi bi-hdd-rack text-muted"></i> Environment Information
                                </h6>
                                <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 px-3 py-2">
                                    <i class="bi bi-lock me-1"></i> Read-Only Configuration
                                </span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-borderless align-middle mb-0">
                                    <tbody>
                                        <tr class="border-bottom border-light">
                                            <td class="ps-0 py-3" style="width: 30%;"><span class="text-muted fw-bold small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Application Version</span></td>
                                            <td class="pe-0 fw-bold text-dark">v1.2.5 <span class="badge bg-indigo text-white ms-2 shadow-sm">STABLE</span></td>
                                        </tr>
                                        <tr class="border-bottom border-light">
                                            <td class="ps-0 py-3"><span class="text-muted fw-bold small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Root Server URL</span></td>
                                            <td class="pe-0"><a href="<?= BASE_URL ?>" class="text-decoration-none text-indigo fw-medium"><?= BASE_URL ?> <i class="bi bi-box-arrow-up-right ms-1"></i></a></td>
                                        </tr>
                                        <tr class="border-bottom border-light">
                                            <td class="ps-0 py-3"><span class="text-muted fw-bold small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Maximum Upload Limit</span></td>
                                            <td class="pe-0 fw-bold text-dark"><?= MAX_UPLOAD_SIZE / 1024 / 1024 ?> MB <small class="text-muted fw-normal ms-1">(CSV/TXT)</small></td>
                                        </tr>
                                        <tr class="border-bottom border-light">
                                            <td class="ps-0 py-3"><span class="text-muted fw-bold small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Server Software</span></td>
                                            <td class="pe-0 text-dark"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0 py-3"><span class="text-muted fw-bold small text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Local Timestamp</span></td>
                                            <td class="pe-0 text-dark font-monospace bg-light px-2 py-1 rounded d-inline-block mt-2"><?= date('Y-m-d H:i:s') ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-indigo {
        background-color: #4f46e5;
    }

    .text-indigo {
        color: #4f46e5;
    }

    .btn-outline-indigo {
        color: #4f46e5;
        border-color: #4f46e5;
    }

    .btn-outline-indigo:hover {
        background-color: #4f46e5;
        color: white;
    }

    .status-orb {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .shadow-success {
        box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
    }
</style>

<?php include '../includes/footer.php'; ?>