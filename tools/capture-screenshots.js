/**
 * Screenshot Capture Script for Lab Programming Allocation System
 * Automatically captures screenshots of all pages and saves them with descriptive names
 * 
 * Usage: node tools/capture-screenshots.js
 * Prerequisites: 
 * - Server must be running on http://localhost/LAB PROGRAMMING ALLOCATION SYSTEM
 * - XAMPP Apache + MySQL must be running
 * - Run: npm install puppeteer (one-time setup)
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// Configuration
const BASE_URL = 'http://localhost/LAB PROGRAMMING ALLOCATION SYSTEM';
const SCREENSHOTS_BASE = path.join(__dirname, '..', 'documentation', 'screenshots');
const OUTPUT_FOLDER = SCREENSHOTS_BASE; // default; overridden by viewport/fullheight
const DELAY_SECONDS = 3;
const VIEWPORT_SIZE = { width: 1920, height: 1080 };
// Two parent folders: viewport (visible area only) and fullheight (full page scroll)
const CAPTURE_MODES = ['viewport', 'fullheight'];

// Login credentials (from auth.php access keys: Admin@123, Password@123)
const credentials = {
    admin: {
        usernames: ['srinath', 'admin'],
        password: 'Admin@123'
    },
    staff: {
        usernames: ['STF100', 'STAFF001'],
        password: 'Admin@123'
    },
    student: {
        usernames: ['23-AI-001', '23-CS-001'],
        passwords: ['Password@123', 'admin123']  // try both; auth page uses Password@123
    }
};

// Screenshot configuration - Page list from Appendix A
const screenshots = [
    // Common Pages (No login required)
    { number: 1, name: 'Landing_Page', path: 'index.php', folder: '01_Common', description: 'Landing Page - System entry point', loginRequired: false, role: '' },
    { number: 2, name: 'Login_Page', path: 'auth.php', folder: '01_Common', description: 'Login Page - Unified authentication', loginRequired: false, role: '' },
    
    // Admin Module (Admin login required)
    { number: 3, name: 'Admin_Dashboard', path: 'admin/dashboard.php', folder: '02_Admin', description: 'Admin Dashboard - Statistics and overview', loginRequired: true, role: 'admin' },
    { number: 4, name: 'Manage_Students', path: 'admin/manage_students.php', folder: '02_Admin', description: 'Manage Students - CRUD interface', loginRequired: true, role: 'admin' },
    { number: 5, name: 'Manage_Labs', path: 'admin/manage_labs.php', folder: '02_Admin', description: 'Manage Labs - Lab configuration', loginRequired: true, role: 'admin' },
    { number: 6, name: 'Manage_Questions', path: 'admin/manage_questions.php', folder: '02_Admin', description: 'Manage Questions - Question bank', loginRequired: true, role: 'admin' },
    { number: 7, name: 'Manage_Staff', path: 'admin/manage_staff.php', folder: '02_Admin', description: 'Manage Staff - Staff management', loginRequired: true, role: 'admin' },
    { number: 8, name: 'Manage_Subjects', path: 'admin/manage_subjects.php', folder: '02_Admin', description: 'Manage Subjects - Subject configuration', loginRequired: true, role: 'admin' },
    
    // Staff Module (Staff login required)
    { number: 9, name: 'Staff_Dashboard', path: 'staff/dashboard.php', folder: '03_Staff', description: 'Staff Dashboard - Personal workspace', loginRequired: true, role: 'staff' },
    { number: 10, name: 'Start_Exam', path: 'staff/start_exam.php', folder: '03_Staff', description: 'Start Exam - Session creation', loginRequired: true, role: 'staff' },
    { number: 11, name: 'Monitor_Exam', path: 'staff/monitor_exam.php', folder: '03_Staff', description: 'Monitor Exam - Real-time grid view', loginRequired: true, role: 'staff' },
    { number: 12, name: 'Evaluate_Submissions', path: 'staff/evaluate.php', folder: '03_Staff', description: 'Evaluate Submissions - Code evaluation', loginRequired: true, role: 'staff' },
    { number: 13, name: 'View_Results', path: 'staff/view_result.php', folder: '03_Staff', description: 'View Results - Results summary', loginRequired: true, role: 'staff' },
    
    // Student Module (Student login required)
    { number: 14, name: 'Student_Dashboard', path: 'student/dashboard.php', folder: '04_Student', description: 'Student Dashboard - Waiting room', loginRequired: true, role: 'student' },
    { number: 15, name: 'Exam_Interface', path: 'student/exam.php', folder: '04_Student', description: 'Exam Interface - Questions and code editor', loginRequired: true, role: 'student' },
    { number: 16, name: 'Exam_History', path: 'student/history.php', folder: '04_Student', description: 'Exam History - Past results', loginRequired: true, role: 'student' },
    { number: 17, name: 'Student_Profile', path: 'student/profile.php', folder: '04_Student', description: 'Student Profile - Student information', loginRequired: true, role: 'student' },
    
    // Additional Pages
    { number: 18, name: 'View_Session_Details', path: 'admin/view_session_details.php', folder: '05_Additional', description: 'View Session Details - Session overview', loginRequired: true, role: 'admin' },
    { number: 19, name: 'View_Submission', path: 'staff/view_submission.php', folder: '05_Additional', description: 'View Submission - Individual code view', loginRequired: true, role: 'staff' },
    { number: 20, name: 'Reports', path: 'staff/reports.php', folder: '05_Additional', description: 'Reports - Analytics dashboard', loginRequired: true, role: 'staff' }
];

/**
 * Create output folder structure (two parent folders: viewport + fullheight)
 */
function createFolders() {
    const subFolders = ['01_Common', '02_Admin', '03_Staff', '04_Student', '05_Additional'];
    CAPTURE_MODES.forEach(mode => {
        subFolders.forEach(sub => {
            const folder = path.join(SCREENSHOTS_BASE, mode, sub);
            if (!fs.existsSync(folder)) {
                fs.mkdirSync(folder, { recursive: true });
                console.log(`✓ Created folder: ${folder}`);
            }
        });
    });
}

/**
 * Logout current user by visiting role-specific logout URL (so auth.php shows login form)
 * @param {Page} page - Puppeteer page object
 * @param {string} currentRole - Current role (admin, staff, student)
 */
async function logoutCurrentUser(page, currentRole) {
    if (!currentRole) return;
    const logoutUrls = {
        admin: `${BASE_URL}/admin/logout.php`,
        staff: `${BASE_URL}/staff/logout.php`,
        student: `${BASE_URL}/student/logout.php`
    };
    const url = logoutUrls[currentRole];
    if (url) {
        try {
            console.log(`  Logging out from ${currentRole}...`);
            await page.goto(url, { waitUntil: 'networkidle0', timeout: 10000 });
            await page.waitForTimeout(1500);
        } catch (e) {
            console.log(`    (logout warning: ${e.message})`);
        }
    }
}

/**
 * Perform login for a specific role.
 * If already logged in as another role, logout first so auth.php shows the login form.
 * @param {Page} page - Puppeteer page object
 * @param {string} role - Role to login as (admin, staff, student)
 * @param {string} currentRole - Currently logged-in role (to logout first if different)
 * @returns {Promise<boolean>} - True if login successful
 */
async function loginUser(page, role, currentRole) {
    const cred = credentials[role];
    console.log(`  Logging in as ${role}...`);

    // Must logout first if we're logged in as another role (else auth.php redirects to dashboard)
    await logoutCurrentUser(page, currentRole);

    const passwords = cred.passwords || [cred.password];
    for (const username of cred.usernames) {
        for (const password of passwords) {
            try {
                console.log(`    Trying username: ${username}`);

                // Navigate to login page (fresh load so we get new CSRF token)
                await page.goto(`${BASE_URL}/auth.php`, { waitUntil: 'networkidle0', timeout: 15000 });
                await page.waitForSelector('input[name="credential"], input#credential', { timeout: 8000 });

                // Fill credentials (do not clear CSRF hidden field)
                await page.evaluate(({ user, pass }) => {
                    const c = document.querySelector('input[name="credential"]') || document.querySelector('input#credential');
                    const p = document.querySelector('input[name="password"]') || document.querySelector('input#password');
                    if (c) c.value = user;
                    if (p) p.value = pass;
                }, { user: username, pass: password });

                await page.waitForTimeout(300);

                // Submit and wait for navigation (redirect to dashboard)
                const navPromise = page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 10000 }).catch(() => null);
                await page.click('button[type="submit"]');
                await navPromise;
                await page.waitForTimeout(1500);

                const currentUrl = page.url();
                const stillOnAuth = /auth\.php$/i.test(currentUrl) || currentUrl.includes('auth.php?');
                const success = !stillOnAuth && (currentUrl.includes('dashboard') || currentUrl.includes('admin/') || currentUrl.includes('staff/') || currentUrl.includes('student/') || currentUrl.includes('manage') || currentUrl.includes('exam') || currentUrl.includes('monitor') || currentUrl.includes('evaluate') || currentUrl.includes('view') || currentUrl.includes('reports') || currentUrl.includes('profile') || currentUrl.includes('history'));

                if (success) {
                    console.log(`  ✓ Login successful with: ${username}`);
                    return true;
                }
                console.log(`    ✗ Login failed with: ${username} (still on auth or wrong page)`);
            } catch (error) {
                console.log(`    ✗ Error with ${username}: ${error.message}`);
            }
        }
    }

    console.log(`  ✗ All login attempts failed for role: ${role}`);
    console.log(`  Check auth.php access keys: Admin@123 (admin/staff), Password@123 (student)`);
    return false;
}

/**
 * Logout user for public pages (use role-specific logout when we know current role)
 */
async function logoutUser(page, currentRole) {
    await logoutCurrentUser(page, currentRole || 'admin');
    await page.waitForTimeout(500);
}

/**
 * Capture screenshot of a page (viewport and/or fullheight)
 * @param {Page} page - Puppeteer page object
 * @param {Object} shot - Screenshot configuration object
 * @param {string} currentRole - Currently logged in role
 * @param {string} mode - 'viewport' or 'fullheight'
 * @returns {Promise<boolean>} - True if capture successful
 */
async function captureScreenshot(page, shot, currentRole, mode) {
    const url = `${BASE_URL}/${shot.path}`;
    const filename = `${String(shot.number).padStart(2, '0')}_${shot.name}.png`;
    const outDir = path.join(SCREENSHOTS_BASE, mode, shot.folder);
    const fullPath = path.join(outDir, filename);

    console.log(`\n[${shot.number}/${screenshots.length}] Capturing: ${shot.name} (${mode})...`);
    console.log(`  URL: ${url}`);

    try {
        // Handle login if required
        if (shot.loginRequired) {
            if (currentRole !== shot.role) {
                const loginSuccess = await loginUser(page, shot.role, currentRole);
                if (!loginSuccess) {
                    console.log(`  ✗ Skipping due to login failure`);
                    return { success: false, role: currentRole };
                }
                currentRole = shot.role;
            }
        } else {
            if (currentRole !== '') {
                console.log(`  Logging out for public page...`);
                await logoutUser(page, currentRole);
                currentRole = '';
            }
        }

        // Navigate to page
        await page.goto(url, { waitUntil: 'networkidle2', timeout: 30000 });
        await page.waitForTimeout(DELAY_SECONDS * 1000);
        await page.waitForTimeout(2000);

        const fullPage = mode === 'fullheight';
        await page.screenshot({
            path: fullPath,
            fullPage,
            type: 'png'
        });

        console.log(`  ✓ Saved: ${fullPath}`);
        return { success: true, role: currentRole };
    } catch (error) {
        console.log(`  ✗ Failed: ${error.message}`);
        return { success: false, role: currentRole };
    }
}

/**
 * Main function
 */
async function main() {
    console.log('\n========================================');
    console.log('  SCREENSHOT CAPTURE TOOL');
    console.log('  Lab Programming Allocation System');
    console.log('========================================\n');

    console.log(`Base URL: ${BASE_URL}`);
    console.log(`Output: ${SCREENSHOTS_BASE}`);
    console.log(`Modes: ${CAPTURE_MODES.join(', ')} (two parent folders)`);
    console.log(`Total Pages: ${screenshots.length}\n`);

    createFolders();

    console.log('Launching browser...');
    const browser = await puppeteer.launch({
        headless: false,
        defaultViewport: VIEWPORT_SIZE,
        args: ['--start-maximized']
    });

    const page = await browser.newPage();
    await page.setViewport(VIEWPORT_SIZE);

    let captured = 0;
    let failed = 0;
    let currentRole = '';

    // Capture for each mode: viewport (visible area) and fullheight (full page)
    for (const mode of CAPTURE_MODES) {
        console.log(`\n========== MODE: ${mode.toUpperCase()} ==========`);
        for (const shot of screenshots) {
            const result = await captureScreenshot(page, shot, currentRole, mode);
            if (result.success) {
                captured++;
                currentRole = result.role;
            } else {
                failed++;
            }
        }
    }

    await browser.close();

    console.log('\n========================================');
    console.log('  CAPTURE COMPLETE');
    console.log('========================================');
    console.log(`Captured: ${captured}`);
    console.log(`Failed: ${failed}`);
    console.log(`\nScreenshots saved to:`);
    console.log(`  - ${path.join(SCREENSHOTS_BASE, 'viewport')} (visible viewport only)`);
    console.log(`  - ${path.join(SCREENSHOTS_BASE, 'fullheight')} (full page height)`);
    console.log('\n');
}

// Run main function
main().catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
