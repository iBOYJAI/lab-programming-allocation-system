<?php

/**
 * Unified Authentication Page - Notion Style Redesign
 * Clean, Minimal, Functional
 */

require_once 'config/database.php';
require_once 'includes/security.php';

startSecureSession();

// Redirect if already logged in
if (checkSession()) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'staff':
            redirect('staff/dashboard.php');
            break;
        case 'student':
            redirect('student/exam.php');
            break;
    }
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please refresh and try again.";
    } else {
        $credential = trim($_POST['credential'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($credential) || empty($password)) {
            $error = "Please enter both credential and password";
        } else {
            try {
                $user = null;
                $role = null;

                // Attempt 1: Check if it's an admin
                try {
                    $sql = "SELECT * FROM users WHERE username = ?";
                    $result = dbQuery($sql, [$credential]);
                    if (!empty($result)) {
                        $user = $result[0];
                        $role = 'admin';
                    }
                } catch (Exception $e) {
                }

                // Attempt 2: Check if it's a staff member  
                if (!$user) {
                    try {
                        $sql = "SELECT * FROM staff WHERE staff_id = ?";
                        $result = dbQuery($sql, [$credential]);
                        if (!empty($result)) {
                            $user = $result[0];
                            $role = 'staff';
                        }
                    } catch (Exception $e) {
                    }
                }

                // Attempt 3: Check if it's a student
                if (!$user) {
                    try {
                        $sql = "SELECT * FROM students WHERE roll_number = ?";
                        $result = dbQuery($sql, [$credential]);
                        if (!empty($result)) {
                            $user = $result[0];
                            $role = 'student';
                        }
                    } catch (Exception $e) {
                    }
                }

                // Verify password and login
                if ($user) {
                    if (password_verify($password, $user['password'])) {
                        session_regenerate_id(true);
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_role'] = $role;
                        $_SESSION['last_activity'] = time();

                        switch ($role) {
                            case 'admin':
                                $_SESSION['username'] = $user['username'];
                                logActivity($user['id'], 'admin', 'login', 'Admin logged in: ' . $user['username']);
                                redirect('admin/dashboard.php');
                                break;
                            case 'staff':
                                $_SESSION['staff_id'] = $user['staff_id'];
                                $_SESSION['staff_name'] = $user['name'];
                                $_SESSION['email'] = $user['email'];
                                $_SESSION['department'] = $user['department'] ?? '';
                                logActivity($user['id'], 'staff', 'login', 'Staff logged in: ' . $user['name']);
                                redirect('staff/dashboard.php');
                                break;
                            case 'student':
                                $_SESSION['roll_number'] = $user['roll_number'];
                                $_SESSION['student_name'] = $user['name'];
                                $_SESSION['department'] = $user['department'] ?? '';
                                $_SESSION['system_no'] = $user['system_no'] ?? 0;
                                logActivity($user['id'], 'student', 'login', 'Student logged in: ' . $user['name']);
                                redirect('student/exam.php');
                                break;
                        }
                    } else {
                        $error = "Incorrect password.";
                        logActivity(0, 'unknown', 'failed_login', "Failed login - wrong password for: $credential");
                    }
                } else {
                    $error = "User not found.";
                    logActivity(0, 'unknown', 'failed_login', "Failed login - user not found: $credential");
                }
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $error = "System error. Please contact administrator.";
            }
        }
    }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | LPAS Technical Ingress</title>

    <!-- Professional Typography & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">

    <style>
        :root {
            --bg-primary: #ffffff;
            --accent-dark: #000000;
            --text-primary: #000000;
            --text-secondary: #444444;
            --tech-blue: #0066ff;
            --mono-font: 'JetBrains Mono', monospace;
            --body-font: 'Plus Jakarta Sans', sans-serif;
            --heading-font: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: var(--body-font);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-image: radial-gradient(circle at 2px 2px, #f0f0f0 1px, transparent 0);
            background-size: 40px 40px;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 480px;
            padding: 20px;
            z-index: 10;
        }

        .auth-card {
            background: #fff;
            border: 2px solid var(--accent-dark);
            box-shadow: 15px 15px 0px var(--accent-dark);
            padding: 50px;
            position: relative;
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        .auth-card::before {
            content: "SECURE_INGRESS_MODE";
            position: absolute;
            top: -12px;
            left: 20px;
            background: var(--accent-dark);
            color: #fff;
            padding: 2px 12px;
            font-family: var(--mono-font);
            font-size: 0.65rem;
            letter-spacing: 1.5px;
        }

        .brand-logo {
            font-family: var(--heading-font);
            font-weight: 900;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo i {
            background: var(--accent-dark);
            color: #fff;
            padding: 6px 10px;
            font-size: 1rem;
        }

        .form-label {
            font-family: var(--mono-font);
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .form-control {
            border: 2px solid #eeeeee;
            border-radius: 0;
            padding: 14px 18px;
            font-family: var(--mono-font);
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .form-control:focus {
            border-color: var(--accent-dark);
            box-shadow: none;
            background: #fafafa;
        }

        .btn-tech {
            background: var(--accent-dark);
            color: #fff;
            border: 2px solid var(--accent-dark);
            border-radius: 0;
            padding: 16px;
            font-family: var(--heading-font);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 2px;
            width: 100%;
            margin-top: 10px;
            transition: 0.3s;
        }

        .btn-tech:hover {
            background: #fff;
            color: var(--accent-dark);
        }

        .alert-tech {
            background: #fff;
            border: 2px solid #ff3333;
            color: #ff3333;
            border-radius: 0;
            padding: 15px;
            font-family: var(--mono-font);
            font-size: 0.8rem;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .access-keys {
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 25px;
        }

        .access-label {
            font-family: var(--mono-font);
            font-size: 0.65rem;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            display: block;
            text-align: center;
        }

        .key-pill {
            display: inline-block;
            border: 1px solid #ddd;
            padding: 4px 12px;
            font-family: var(--mono-font);
            font-size: 0.7rem;
            color: #666;
            margin: 4px;
            cursor: pointer;
            transition: 0.2s;
        }

        .key-pill:hover {
            border-color: var(--accent-dark);
            color: var(--accent-dark);
            background: #fdfdfd;
        }

        .back-nav {
            margin-top: 30px;
            text-align: center;
        }

        .back-nav a {
            color: #999;
            text-decoration: none;
            font-family: var(--mono-font);
            font-size: 0.75rem;
            transition: 0.3s;
        }

        .back-nav a:hover {
            color: var(--accent-dark);
        }

        /* Float Anim */
        @keyframes float {
            0% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-10px);
            }

            100% {
                transform: translateY(0);
            }
        }

        .tech-bg-label {
            position: fixed;
            bottom: 40px;
            right: 40px;
            font-family: var(--mono-font);
            font-size: 0.7rem;
            opacity: 0.1;
            writing-mode: vertical-rl;
            letter-spacing: 10px;
            pointer-events: none;
        }
    </style>
</head>

<body>

    <div class="tech-bg-label">AUTHENTICATION_PROTOCOL_v2.0</div>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="brand-logo">
                <i class="bi bi-cpu"></i>
                <span>LPAS <span class="fw-300 opacity-50">CORE</span></span>
            </div>

            <?php if ($error): ?>
                <div class="alert-tech">
                    <i class="bi bi-shield-slash"></i>
                    <span>ERROR: <?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <div class="mb-4">
                    <label for="credential" class="form-label">System Descriptor</label>
                    <input type="text" class="form-control" id="credential" name="credential"
                        placeholder="ID / Roll Number"
                        value="<?= isset($_POST['credential']) ? htmlspecialchars($_POST['credential']) : '' ?>"
                        required autofocus>
                </div>

                <div class="mb-5">
                    <label for="password" class="form-label">Access Token</label>
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="Password Certificate" required>
                </div>

                <button type="submit" class="btn btn-tech">Authorize Access</button>
            </form>

            <div class="access-keys">
                <span class="access-label">// SYSTEM ACCESS KEYS</span>
                <div class="text-center">
                    <div class="key-pill" onclick="fillCreds('srinath', 'Admin@123')">ADMIN: srinath</div>
                    <div class="key-pill" onclick="fillCreds('STF100', 'Admin@123')">STAFF: STF100</div>
                    <div class="key-pill" onclick="fillCreds('23-AI-001', 'Password@123')">STUDENT: 23-AI-001</div>
                </div>
            </div>
        </div>

        <div class="back-nav">
            <a href="index.php"># RETURN_TO_ROOT_DOMAIN</a>
        </div>
    </div>

    <script>
        function fillCreds(u, p) {
            document.getElementById('credential').value = u;
            document.getElementById('password').value = p;
            document.getElementById('credential').focus();
        }
    </script>
</body>

</html>