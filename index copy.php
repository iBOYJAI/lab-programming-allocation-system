<?php

/**
 * LAB PROGRAMMING ALLOCATION SYSTEM
 * Premium Monochrome Technical Landing Page
 */

require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/functions.php';

startSecureSession();

// Redirect if already logged in
if (checkSession()) {
    switch ($_SESSION['user_role'] ?? '') {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'staff':
            redirect('staff/dashboard.php');
            break;
        case 'student':
            redirect('student/dashboard.php');
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Allocation System | Premium Tech Infrastructure</title>

    <!-- Fonts: Minimalist High-End Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">

    <style>
        :root {
            --bg-primary: #ffffff;
            --text-primary: #000000;
            --text-secondary: #4a4a4a;
            --border-color: #e5e5e5;
            --accent-soft: #f9f9f9;
            --mono-font: 'JetBrains Mono', monospace;
            --body-font: 'Plus Jakarta Sans', sans-serif;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: var(--body-font);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* --- Global Elements --- */
        .section-padding {
            padding: 120px 0;
        }

        .mono-text {
            font-family: var(--mono-font);
        }

        .fw-800 {
            font-weight: 800;
        }

        .line-accent {
            width: 40px;
            height: 2px;
            background: #000;
            margin: 20px 0;
        }

        .btn-black {
            background: #000;
            color: #fff;
            padding: 14px 32px;
            border-radius: 0;
            border: 1px solid #000;
            font-weight: 600;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.85rem;
        }

        .btn-black:hover {
            background: #fff;
            color: #000;
            transform: translateY(-2px);
        }

        .btn-outline-black {
            background: transparent;
            color: #000;
            padding: 14px 32px;
            border-radius: 0;
            border: 1px solid #000;
            font-weight: 600;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.85rem;
        }

        .btn-outline-black:hover {
            background: #000;
            color: #fff;
        }

        /* --- Header --- */
        nav.navbar {
            padding: 25px 0;
            background: var(--bg-primary);
            border-bottom: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .navbar-brand img {
            height: 28px;
            filter: grayscale(1);
        }

        .nav-link {
            color: var(--text-primary) !important;
            font-weight: 600;
            font-size: 0.9rem;
            margin-left: 30px;
            opacity: 0.6;
            transition: var(--transition);
        }

        .nav-link:hover {
            opacity: 1;
        }

        /* --- Hero --- */
        .hero-section {
            min-height: 90vh;
            display: flex;
            align-items: center;
            position: relative;
        }

        .hero-title {
            font-size: clamp(3rem, 8vw, 6rem);
            letter-spacing: -0.04em;
            line-height: 0.95;
            margin-bottom: 30px;
        }

        .hero-desc {
            max-width: 500px;
            font-size: 1.15rem;
            color: var(--text-secondary);
            margin-bottom: 40px;
        }

        /* --- Grid & Cards --- */
        .tech-card {
            border: 1px solid var(--border-color);
            padding: 40px;
            height: 100%;
            transition: var(--transition);
        }

        .tech-card:hover {
            border-color: #000;
            background: var(--accent-soft);
        }

        .tech-card .icon-box {
            font-size: 2.5rem;
            margin-bottom: 25px;
        }

        .tech-card h4 {
            font-weight: 800;
            margin-bottom: 15px;
            letter-spacing: -0.02em;
        }

        .tech-card p {
            font-size: 0.95rem;
            color: var(--text-secondary);
            margin-bottom: 0;
        }

        /* --- Algorithm Analysis Section --- */
        .algo-analysis {
            background: #000;
            color: #fff;
            border-radius: 0;
        }

        .code-block {
            background: #111;
            padding: 30px;
            border: 1px solid #333;
            color: #fff;
            font-family: var(--mono-font);
            font-size: 0.85rem;
            line-height: 1.7;
            position: relative;
            overflow: hidden;
        }

        .code-block::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(0deg, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0) 20%);
            pointer-events: none;
        }

        /* --- Stats --- */
        .stat-item h2 {
            font-size: 4rem;
            font-weight: 800;
        }

        .stat-item p {
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 2px;
        }

        /* --- Animations --- */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .reveal {
            animation: fadeInUp 0.8s ease forwards;
            opacity: 0;
        }

        .delay-1 {
            animation-delay: 0.2s;
        }

        .delay-2 {
            animation-delay: 0.4s;
        }

        .delay-3 {
            animation-delay: 0.6s;
        }

        /* --- Footer --- */
        footer {
            border-top: 1px solid var(--border-color);
            padding: 80px 0;
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-3" href="#">
                <img src="assets/images/Notion-Resources/Notion-Icons/Regular/svg/ni-server.svg" alt="Logo">
                <span class="fw-800 text-uppercase ls-1 small">LABS Architecture</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
                <i class="bi bi-list fs-2"></i>
            </button>
            <div class="collapse navbar-collapse" id="navContent">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="#core">Architecture</a></li>
                    <li class="nav-item"><a class="nav-link" href="#algorithm">Logic</a></li>
                    <li class="nav-item"><a class="nav-link" href="#security">Security</a></li>
                    <li class="nav-item"><a class="nav-link fw-bold text-dark border-bottom border-dark" href="presentation.php">Presentation Mode</a></li>
                    <li class="nav-item">
                        <a class="btn btn-black ms-lg-4" href="auth.php">System Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section container">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <div class="reveal">
                    <span class="mono-text text-uppercase small ls-2 d-block mb-3 opacity-50">// Ver 2.0.1 Stable</span>
                    <h1 class="hero-title fw-800">Next-Gen Lab <br>Allocation.</h1>
                    <p class="hero-desc">An autonomous infrastructure for programming lab exams. Engineered for zero-collision question distribution and real-time monitoring.</p>
                    <div class="d-flex gap-3">
                        <a href="auth.php" class="btn btn-black">Initialize Session</a>
                        <a href="#core" class="btn btn-outline-black">Deep Dive</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block position-relative">
                <div class="reveal delay-2">
                    <img src="assets/images/Notion-Resources/Office-Club/Regular/png/oc-project-development.png" class="img-fluid" alt="Architecture Illustration">
                    <div class="position-absolute bottom-0 end-0 p-4 bg-white border border-dark transform-10 translate-middle-y d-none d-xl-block">
                        <p class="mono-text small mb-0 fw-bold">LATENCY: <span class="text-success">0.02ms</span></p>
                        <p class="mono-text small mb-0 fw-bold">INTEGRITY: <span class="text-success">V-Verified</span></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Architecture Grid -->
    <section id="core" class="section-padding border-top">
        <div class="container">
            <div class="row mb-5 reveal">
                <div class="col-lg-6">
                    <h2 class="fw-800 display-5 mb-3">Modular <br>Architecture</h2>
                    <div class="line-accent"></div>
                </div>
                <div class="col-lg-6 d-flex align-items-center">
                    <p class="text-secondary">A highly decoupled three-tier system designed for concurrent access across multiple departmental laboratories.</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4 reveal delay-1">
                    <div class="tech-card">
                        <div class="icon-box text-dark">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h4 class="text-uppercase ls-1">Admin Central</h4>
                        <ul class="list-unstyled small text-secondary">
                            <li class="mb-2"><i class="bi bi-chevron-right me-2"></i> Master User Control</li>
                            <li class="mb-2"><i class="bi bi-chevron-right me-2"></i> Lab Infrastructure Sync</li>
                            <li class="mb-2"><i class="bi bi-chevron-right me-2"></i> System Audit Analytics</li>
                        </ul>
                        <p class="mt-3">The command bridge for total departmental oversight and resource management.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal delay-2">
                    <div class="tech-card">
                        <div class="icon-box text-dark">
                            <i class="bi bi-eye"></i>
                        </div>
                        <h4 class="text-uppercase ls-1">Staff Oversight</h4>
                        <ul class="list-unstyled small text-secondary">
                            <li class="mb-2"><i class="bi bi-chevron-right me-2"></i> Session Orchestration</li>
                            <li class="mb-2"><i class="bi bi-chevron-right me-2"></i> Real-time Invigilation</li>
                            <li class="mb-2"><i class="bi bi-chevron-right me-2"></i> Dynamic Manual Overrides</li>
                        </ul>
                        <p class="mt-3">A dedicated interface for lab instructors to monitor and control active exam states.</p>
                    </div>
                </div>
                <div class="col-md-4 reveal delay-3">
                    <div class="tech-card">
                        <div class="icon-box text-dark">
                            <i class="bi bi-code-square"></i>
                        </div>
                        <h4 class="text-uppercase ls-1">Candidate Engine</h4>
                        <ul class="list-unstyled small text-secondary">
                            <li class="mb-2"><i class="bi bi-chevron-right me-2"></i> Secure Code Terminal</li>
                            <li class="mb-2"><i class="bi bi-chevron-right me-2"></i> Isolated Question Access</li>
                            <li class="mb-2"><i class="bi bi-chevron-right me-2"></i> Auto-Save Persistence</li>
                        </ul>
                        <p class="mt-3">Low-latency environment engineered for consistent UX during high-pressure evaluations.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Deep Logic Detail (The Algorithm) -->
    <section id="algorithm" class="section-padding algo-analysis">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0 pe-lg-5">
                    <h2 class="fw-800 display-4 mb-4">The Logic Core</h2>
                    <p class="lead opacity-75 mb-5">Our Smart Allocation Algorithm ensures 100% academic integrity by mathematically preventing cheating in high-density lab environments.</p>

                    <div class="accordion accordion-flush" id="algoAccordion">
                        <div class="accordion-item bg-transparent border-bottom border-dark">
                            <h2 class="accordion-header">
                                <button class="accordion-button bg-transparent text-white collapsed px-0 shadow-none fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                    01. Collision Matrix Analysis
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#algoAccordion">
                                <div class="accordion-body px-0 text-white small">
                                    The algorithm analyzes system numbers in a 2D spatial context. It checks for collisions within ±1 (adjacent seats) and ±10 (vertical row neighbors) to ensure unique question sets.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item bg-transparent border-bottom border-dark">
                            <h2 class="accordion-header">
                                <button class="accordion-button bg-transparent text-white collapsed px-0 shadow-none fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                    02. Seed-Based Randomization
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#algoAccordion">
                                <div class="accordion-body px-0 text-white small">
                                    Each allocation session generates a unique cryptographic seed combined with system timestamps, ensuring that even if parameters are identical, the distribution remains unpredictable.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item bg-transparent border-bottom border-dark">
                            <h2 class="accordion-header">
                                <button class="accordion-button bg-transparent text-white collapsed px-0 shadow-none fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                    03. Atomic Transaction Safety
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#algoAccordion">
                                <div class="accordion-body px-0 text-white small">
                                    Leveraging PDO transactions, we ensure that the thousands of allocation data points are committed to the MySQL engine in a single atomic operation, preventing partial states.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="code-block rounded">
                        <div class="d-flex justify-content-between mb-3 opacity-50 border-bottom border-secondary pb-2">
                            <span>// allocation_algorithm_v2.php</span>
                            <span>PHP 7.4+ PDO</span>
                        </div>
                        <pre><code>function allocateQuestions($sid, $subId, $students) {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT id FROM questions...");
    
    // Collision Detection Matrix
    $adj_systems = [
        $curr - 1, $curr + 1,
        $curr - 10, $curr + 10
    ];
    
    while ($collision && $attempt < 50) {
        $q1 = $shuffled[rand_ptr];
        $q2 = $shuffled[half_ptr];
        
        foreach($adj_systems as $adj) {
            if ($alloc_map[$adj] matches candidate) {
                $collision = true;
                break;
            }
        }
    }
    return $final_matrix;
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- System Workflow Lifecycle -->
    <section id="workflow" class="section-padding border-top bg-white">
        <div class="container">
            <div class="row mb-5 reveal">
                <div class="col-lg-12 text-center">
                    <span class="mono-text text-uppercase small ls-2 opacity-50 mb-3 d-block">Infrastructure Flow</span>
                    <h2 class="fw-800 display-4">System Lifecycle</h2>
                    <div class="line-accent mx-auto"></div>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="row g-0 border">
                        <div class="col-md-3 border-end p-4 reveal delay-1">
                            <h2 class="fw-800 mb-3 opacity-25">01</h2>
                            <h6 class="fw-800 text-uppercase small">Initialization</h6>
                            <p class="small text-secondary mb-0">Admin imports laboratories and bulk student data via CSV into the MySQL engine.</p>
                        </div>
                        <div class="col-md-3 border-end p-4 reveal delay-2">
                            <h2 class="fw-800 mb-3 opacity-25">02</h2>
                            <h6 class="fw-800 text-uppercase small">Allocation</h6>
                            <p class="small text-secondary mb-0">Staff triggers the collision-detection algorithm to assign questions across labs.</p>
                        </div>
                        <div class="col-md-3 border-end p-4 reveal delay-3">
                            <h2 class="fw-800 mb-3 opacity-25">03</h2>
                            <h6 class="fw-800 text-uppercase small">Execution</h6>
                            <p class="small text-secondary mb-0">Students log in to a hardened terminal to code and submit their programming solutions.</p>
                        </div>
                        <div class="col-md-3 p-4 reveal delay-4">
                            <h2 class="fw-800 mb-3 opacity-25">04</h2>
                            <h6 class="fw-800 text-uppercase small">Evaluation</h6>
                            <p class="small text-secondary mb-0">Staff reviews code logs and marks are committed to the secure departmental repository.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Database Schema Snapshot -->
    <section id="schema" class="section-padding border-top bg-light">
        <div class="container">
            <div class="row mb-5 reveal">
                <div class="col-lg-6">
                    <h2 class="fw-800 display-5 mb-3">Database <br>Architecture</h2>
                    <div class="line-accent"></div>
                </div>
                <div class="col-lg-6 d-flex align-items-center">
                    <p class="text-secondary">A normalized relational schema optimized for high-concurrency during peak laboratory examination hours.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 reveal delay-1">
                    <div class="p-4 bg-white border h-100">
                        <h6 class="mono-text small fw-bold text-uppercase mb-3">// Core Entities</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-borderless small mb-0">
                                <thead>
                                    <tr class="border-bottom">
                                        <th class="py-2">Table Name</th>
                                        <th class="py-2">Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="mono-text py-2">users</td>
                                        <td>Master credentials with role-based indexing</td>
                                    </tr>
                                    <tr>
                                        <td class="mono-text py-2">subjects</td>
                                        <td>Academic course metadata & question clusters</td>
                                    </tr>
                                    <tr>
                                        <td class="mono-text py-2">exam_sessions</td>
                                        <td>Temporal lab session parameters</td>
                                    </tr>
                                    <tr>
                                        <td class="mono-text py-2">allocations</td>
                                        <td>Spatial student-to-question mappings</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 reveal delay-2">
                    <div class="p-4 bg-white border h-100">
                        <h6 class="mono-text small fw-bold text-uppercase mb-3">// Integrity Constraints</h6>
                        <ul class="list-unstyled small text-secondary">
                            <li class="mb-2"><i class="bi bi-link-45deg me-2 text-dark"></i> Foreign Key cascades on student-session relationships.</li>
                            <li class="mb-2"><i class="bi bi-clock-history me-2 text-dark"></i> Timestamp-based submission tracking for audit trails.</li>
                            <li class="mb-2"><i class="bi bi-shield-shaded me-2 text-dark"></i> Encrypted authentication vectors for all access levels.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    <!-- Technical Stack Section -->
    <section id="stack" class="section-padding border-top">
        <div class="container">
            <div class="row mb-5 reveal">
                <div class="col-lg-12 text-center">
                    <span class="mono-text text-uppercase small ls-2 opacity-50 mb-3 d-block">System Core</span>
                    <h2 class="fw-800 display-4">Technology Stack</h2>
                    <div class="line-accent mx-auto"></div>
                </div>
            </div>
            <div class="row g-4 text-center">
                <div class="col-md-3 reveal delay-1">
                    <div class="p-4 border">
                        <h6 class="fw-800 text-uppercase small ls-1 mb-3">Engine</h6>
                        <p class="mono-text mb-0">PHP 8.2 Runtime</p>
                        <p class="small text-secondary mt-2">Server-side logic & Dynamic Routing</p>
                    </div>
                </div>
                <div class="col-md-3 reveal delay-2">
                    <div class="p-4 border">
                        <h6 class="fw-800 text-uppercase small ls-1 mb-3">Storage</h6>
                        <p class="mono-text mb-0">MySQL 8.0</p>
                        <p class="small text-secondary mt-2">Atomic Transactions & PDO Layer</p>
                    </div>
                </div>
                <div class="col-md-3 reveal delay-3">
                    <div class="p-4 border">
                        <h6 class="fw-800 text-uppercase small ls-1 mb-3">Interface</h6>
                        <p class="mono-text mb-0">Bootstrap 5.3</p>
                        <p class="small text-secondary mt-2">Responsive SCSS + Vanilla JS Core</p>
                    </div>
                </div>
                <div class="col-md-3 reveal delay-4">
                    <div class="p-4 border">
                        <h6 class="fw-800 text-uppercase small ls-1 mb-3">Assets</h6>
                        <p class="mono-text mb-0">Notion System</p>
                        <p class="small text-secondary mt-2">SVG Iconography & Digital Graphics</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Architecture Visualized -->
    <section id="viz" class="section-padding border-top bg-white">
        <div class="container text-center">
            <div class="row mb-5 reveal">
                <div class="col-lg-12">
                    <span class="mono-text text-uppercase small ls-2 opacity-50 mb-3 d-block">Design Pattern</span>
                    <h2 class="fw-800 display-5">Architectural Flow</h2>
                    <div class="line-accent mx-auto"></div>
                </div>
            </div>
            <div class="d-flex flex-column flex-md-row justify-content-center align-items-center gap-4 reveal">
                <div class="p-4 border border-dark mono-text small bg-light" style="width: 200px;">USER INTERFACE<br><small class="opacity-50">BS5 / Vanilla JS</small></div>
                <div class="fs-2"><i class="bi bi-arrow-right d-none d-md-block"></i><i class="bi bi-arrow-down d-md-none"></i></div>
                <div class="p-4 border border-dark mono-text small bg-dark text-white" style="width: 200px;">MIDDLEWARE<br><small class="opacity-50">PHP / Security API</small></div>
                <div class="fs-2"><i class="bi bi-arrow-right d-none d-md-block"></i><i class="bi bi-arrow-down d-md-none"></i></div>
                <div class="p-4 border border-dark mono-text small bg-light" style="width: 200px;">PERSISTENCE<br><small class="opacity-50">MySQL / InnoDB</small></div>
            </div>
            <p class="mt-5 text-secondary small">A decoupled MVC-inspired approach ensuring modularity and rapid deployment.</p>
        </div>
    </section>

    <!-- Future Roadmap -->
    <section id="roadmap" class="section-padding border-top bg-light">
        <div class="container">
            <div class="row mb-5 reveal">
                <div class="col-lg-12 text-center">
                    <span class="mono-text text-uppercase small ls-2 opacity-50 mb-3 d-block">Version 3.0</span>
                    <h2 class="fw-800 display-4">Future Roadmap</h2>
                    <div class="line-accent mx-auto"></div>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-6 reveal delay-1">
                    <div class="tech-card bg-white">
                        <div class="icon-box text-dark"><i class="bi bi-robot"></i></div>
                        <h4 class="text-uppercase ls-1">AI Proctoring</h4>
                        <p>Experimental integration of computer vision for automated laboratory monitoring and anomaly detection during exam sessions.</p>
                    </div>
                </div>
                <div class="col-md-6 reveal delay-2">
                    <div class="tech-card bg-white">
                        <div class="icon-box text-dark"><i class="bi bi-cloud-arrow-up"></i></div>
                        <h4 class="text-uppercase ls-1">Distributed Hybrid Sync</h4>
                        <p>Enable multi-campus synchronization, allowing students to access their technical repositories from any departmental laboratory across different campuses.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Project Blueprint / Features -->
    <section class="section-padding bg-black text-white">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4">
                    <h2 class="fw-800 display-5 mb-4">Project <br>Blueprint</h2>
                    <p class="opacity-50">Detailed technical features implemented for scale and reliability.</p>
                </div>
                <div class="col-lg-8">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="p-4 border border-secondary h-100">
                                <h5 class="fw-bold text-uppercase small ls-1 mb-3">Collision Matrix</h5>
                                <p class="small opacity-75">Advanced spatial algorithm calculating seats in ±1 and ±10 vectors to prevent visual cheating paths.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 border border-secondary h-100">
                                <h5 class="fw-bold text-uppercase small ls-1 mb-3">Atomic Commit</h5>
                                <p class="small opacity-75">Single-transaction database updates ensuring 100% data integrity during bulk student allocations.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 border border-secondary h-100">
                                <h5 class="fw-bold text-uppercase small ls-1 mb-3">CSRF Hardening</h5>
                                <p class="small opacity-75">Multi-layered security tokens protecting Every administrative action and laboratory state change.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-4 border border-secondary h-100">
                                <h5 class="fw-bold text-uppercase small ls-1 mb-3">Persistent UI</h5>
                                <p class="small opacity-75">State-aware sidebar and navigation systems that maintain context during high-frequency usage.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Security Focus -->
    <section id="security" class="section-padding">
        <div class="container">
            <div class="row mb-5 text-center justify-content-center">
                <div class="col-lg-8">
                    <h2 class="fw-800 display-5">Uncompromising Integrity</h2>
                    <p class="text-secondary">Security isn't an afterthought; it's the core of the LPAS architecture.</p>
                </div>
            </div>

            <div class="row g-5">
                <div class="col-md-6 d-flex gap-4">
                    <div class="fs-1 text-dark"><i class="bi bi-input-cursor-text"></i></div>
                    <div>
                        <h5 class="fw-bold">Input Sanitization</h5>
                        <p class="text-secondary small">Comprehensive XSS protection and 100% prepared statements (PDO) to mitigate injection vectors.</p>
                    </div>
                </div>
                <div class="col-md-6 d-flex gap-4">
                    <div class="fs-1 text-dark"><i class="bi bi-window-x"></i></div>
                    <div>
                        <h5 class="fw-bold">Environment Hardening</h5>
                        <p class="text-secondary small">Disabled right-click, F12 inspector, and clipboard operations during active candidate sessions.</p>
                    </div>
                </div>
                <div class="col-md-6 d-flex gap-4">
                    <div class="fs-1 text-dark"><i class="bi bi-database-check"></i></div>
                    <div>
                        <h5 class="fw-bold">Auditability</h5>
                        <p class="text-secondary small">Full-spectrum activity logging tracking every administrative and observer action for forensic review.</p>
                    </div>
                </div>
                <div class="col-md-6 d-flex gap-4">
                    <div class="fs-1 text-dark"><i class="bi bi-wifi-off"></i></div>
                    <div>
                        <h5 class="fw-bold">100% Air-Gapped Ready</h5>
                        <p class="text-secondary small">Designed for zero-dependency operation. No cloud reliance; full deployment on local switches.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Technical Specs & Performance -->
    <section class="section-padding bg-light">
        <div class="container">
            <div class="row text-center g-5">
                <div class="col-6 col-lg-3">
                    <div class="stat-item reveal">
                        <h2 class="mono-text">12</h2>
                        <p>Total Modules</p>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item reveal delay-1">
                        <h2 class="mono-text">50</h2>
                        <p>Retry Max-Limit</p>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item reveal delay-2">
                        <h2 class="mono-text">0.1</h2>
                        <p>Commit Latency</p>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-item reveal delay-3">
                        <h2 class="mono-text">10k+</h2>
                        <p>Capacity Scaled</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="section-padding">
        <div class="container text-center">
            <img src="assets/images/Notion-Resources/Office-Club/Regular/png/oc-hi-five.png" width="180" class="mb-4 grayscale">
            <h2 class="fw-800 display-4 mb-4">Initialize Your Infrastructure</h2>
            <p class="text-secondary mb-5 mx-auto" style="max-width: 600px;">Ready to automate your departmental lab management? Log in with your corporate credentials to begin.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="auth.php" class="btn btn-black">Launch System Core</a>
                <a href="faq.php" class="btn btn-outline-black">Documentation</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="small mb-0 opacity-50">&copy; 2026 iBOY Innovation HUB Developer.</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <div class="d-flex gap-4 justify-content-md-end">
                        <a href="presentation.php" class="text-dark x-small fw-bold text-decoration-none text-uppercase ls-1">Project Defense</a>
                        <a href="README.md" class="text-dark x-small fw-bold text-decoration-none text-uppercase ls-1">Project Manifest</a>
                        <a href="tools/test_db.php" class="text-dark x-small fw-bold text-decoration-none text-uppercase ls-1">Diagnostic Mode</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Reveal on scroll
        window.addEventListener('scroll', () => {
            const reveals = document.querySelectorAll('.reveal');
            reveals.forEach(r => {
                const windowHeight = window.innerHeight;
                const revealTop = r.getBoundingClientRect().top;
                const revealPoint = 150;
                if (revealTop < windowHeight - revealPoint) {
                    r.style.opacity = '1';
                    r.style.transform = 'translateY(0)';
                }
            });
        });

        // Initial reveal check
        document.addEventListener('DOMContentLoaded', () => {
            const navbar = document.querySelector('.navbar');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 50) {
                    navbar.style.padding = '15px 0';
                    navbar.style.boxShadow = '0 10px 30px rgba(0,0,0,0.05)';
                } else {
                    navbar.style.padding = '25px 0';
                    navbar.style.boxShadow = 'none';
                }
            });

            // Trigger initial reveals
            window.dispatchEvent(new Event('scroll'));
        });
    </script>
</body>

</html>