@echo off
REM ============================================================================
REM AUTOMATED SCREENSHOT CAPTURE WITH AUTO-LOGIN
REM Opens PowerShell script that handles login automatically
REM ============================================================================

echo ============================================================================
echo   AUTOMATED SCREENSHOT CAPTURE WITH AUTO-LOGIN
echo   Lab Programming Allocation System
echo ============================================================================
echo.
echo This will:
echo 1. Install Selenium module (if needed)
echo 2. Open Chrome browser automatically
echo 3. Login as Admin/Staff/Student as needed
echo 4. Capture all 20 screenshots with proper naming
echo.
echo Make sure:
echo - XAMPP is running (Apache + MySQL)
echo - Your site is accessible at http://localhost/LAB PROGRAMMING ALLOCATION SYSTEM
echo - Default credentials are: admin/admin123, STAFF001/admin123, 2024CS001/admin123
echo.
echo Press any key to start...
pause >nul

powershell.exe -ExecutionPolicy Bypass -File "%~dp0capture_screenshots.ps1"

echo.
echo ============================================================================
echo   CAPTURE COMPLETE!
echo ============================================================================
echo.
echo Check the folder: documentation\screenshots
echo.
pause
