@echo off
REM ============================================================================
REM SIMPLE SCREENSHOT CAPTURE GUIDE
REM Opens all pages in browser for manual screenshot capture
REM ============================================================================

set BASE_URL=http://localhost/LAB PROGRAMMING ALLOCATION SYSTEM
set OUTPUT_FOLDER=documentation\screenshots

echo ============================================================================
echo   SCREENSHOT CAPTURE GUIDE
echo   Lab Programming Allocation System
echo ============================================================================
echo.
echo This script will open all pages in your default browser.
echo You need to capture screenshots manually.
echo.
echo INSTRUCTIONS:
echo 1. When each page opens, press F11 for fullscreen
echo 2. Press Windows + Shift + S to open Snipping Tool
echo 3. Select the full page area
echo 4. Save with the filename shown below each URL
echo.
echo Press any key to start...
pause >nul

REM Create folders
if not exist "%OUTPUT_FOLDER%\01_Common" mkdir "%OUTPUT_FOLDER%\01_Common"
if not exist "%OUTPUT_FOLDER%\02_Admin" mkdir "%OUTPUT_FOLDER%\02_Admin"
if not exist "%OUTPUT_FOLDER%\03_Staff" mkdir "%OUTPUT_FOLDER%\03_Staff"
if not exist "%OUTPUT_FOLDER%\04_Student" mkdir "%OUTPUT_FOLDER%\04_Student"
if not exist "%OUTPUT_FOLDER%\05_Additional" mkdir "%OUTPUT_FOLDER%\05_Additional"

echo.
echo Opening pages...
echo.

REM Common Pages
start "" "%BASE_URL%/index.php"
echo [01] Landing Page - Save as: %OUTPUT_FOLDER%\01_Common\01_Landing_Page.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/auth.php"
echo [02] Login Page - Save as: %OUTPUT_FOLDER%\01_Common\02_Login_Page.png
timeout /t 3 /nobreak >nul

REM Admin Pages
start "" "%BASE_URL%/admin/dashboard.php"
echo [03] Admin Dashboard - Save as: %OUTPUT_FOLDER%\02_Admin\03_Admin_Dashboard.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/admin/manage_students.php"
echo [04] Manage Students - Save as: %OUTPUT_FOLDER%\02_Admin\04_Manage_Students.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/admin/manage_labs.php"
echo [05] Manage Labs - Save as: %OUTPUT_FOLDER%\02_Admin\05_Manage_Labs.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/admin/manage_questions.php"
echo [06] Manage Questions - Save as: %OUTPUT_FOLDER%\02_Admin\06_Manage_Questions.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/admin/manage_staff.php"
echo [07] Manage Staff - Save as: %OUTPUT_FOLDER%\02_Admin\07_Manage_Staff.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/admin/manage_subjects.php"
echo [08] Manage Subjects - Save as: %OUTPUT_FOLDER%\02_Admin\08_Manage_Subjects.png
timeout /t 3 /nobreak >nul

REM Staff Pages
start "" "%BASE_URL%/staff/dashboard.php"
echo [09] Staff Dashboard - Save as: %OUTPUT_FOLDER%\03_Staff\09_Staff_Dashboard.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/staff/start_exam.php"
echo [10] Start Exam - Save as: %OUTPUT_FOLDER%\03_Staff\10_Start_Exam.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/staff/monitor_exam.php"
echo [11] Monitor Exam - Save as: %OUTPUT_FOLDER%\03_Staff\11_Monitor_Exam.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/staff/evaluate.php"
echo [12] Evaluate Submissions - Save as: %OUTPUT_FOLDER%\03_Staff\12_Evaluate_Submissions.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/staff/view_result.php"
echo [13] View Results - Save as: %OUTPUT_FOLDER%\03_Staff\13_View_Results.png
timeout /t 3 /nobreak >nul

REM Student Pages
start "" "%BASE_URL%/student/dashboard.php"
echo [14] Student Dashboard - Save as: %OUTPUT_FOLDER%\04_Student\14_Student_Dashboard.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/student/exam.php"
echo [15] Exam Interface - Save as: %OUTPUT_FOLDER%\04_Student\15_Exam_Interface.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/student/history.php"
echo [16] Exam History - Save as: %OUTPUT_FOLDER%\04_Student\16_Exam_History.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/student/profile.php"
echo [17] Student Profile - Save as: %OUTPUT_FOLDER%\04_Student\17_Student_Profile.png
timeout /t 3 /nobreak >nul

REM Additional Pages
start "" "%BASE_URL%/admin/view_session_details.php"
echo [18] View Session Details - Save as: %OUTPUT_FOLDER%\05_Additional\18_View_Session_Details.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/staff/view_submission.php"
echo [19] View Submission - Save as: %OUTPUT_FOLDER%\05_Additional\19_View_Submission.png
timeout /t 3 /nobreak >nul

start "" "%BASE_URL%/staff/reports.php"
echo [20] Reports - Save as: %OUTPUT_FOLDER%\05_Additional\20_Reports.png
timeout /t 3 /nobreak >nul

echo.
echo ============================================================================
echo   All pages opened!
echo ============================================================================
echo.
echo Capture screenshots using Windows Snipping Tool (Win + Shift + S)
echo Save each screenshot with the filename shown above.
echo.
echo Screenshots folder: %OUTPUT_FOLDER%
echo.
pause
