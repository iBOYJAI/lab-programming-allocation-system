# ============================================================================
# AUTOMATIC SCREENSHOT CAPTURE SCRIPT
# Lab Programming Allocation System - Documentation Screenshots
# ============================================================================
# This script automatically captures screenshots of all pages listed in 
# Appendix A and saves them with proper naming convention.
# ============================================================================

param(
    [string]$BaseUrl = "http://localhost/LAB PROGRAMMING ALLOCATION SYSTEM",
    [string]$OutputFolder = "documentation\screenshots",
    [int]$DelaySeconds = 3,
    [string]$Browser = "chrome"  # chrome, edge, firefox
)

# Create output folder structure
$folders = @(
    "$OutputFolder\01_Common",
    "$OutputFolder\02_Admin",
    "$OutputFolder\03_Staff",
    "$OutputFolder\04_Student",
    "$OutputFolder\05_Additional"
)

foreach ($folder in $folders) {
    if (-not (Test-Path $folder)) {
        New-Item -ItemType Directory -Path $folder -Force | Out-Null
        Write-Host "Created folder: $folder" -ForegroundColor Green
    }
}

# Login credentials (try multiple possible usernames)
$credentials = @{
    admin = @{
        usernames = @("admin", "srinath");  # Try both
        password = "admin123"
        role = "admin"
    }
    staff = @{
        usernames = @("STAFF001", "STF100");  # Try both
        password = "admin123"
        role = "staff"
    }
    student = @{
        usernames = @("2024CS001", "23-CS-001");  # Try both
        password = "admin123"
        role = "student"
    }
}

# Screenshot configuration - Page list from Appendix A with login requirements
$screenshots = @(
    # Common Pages (No login required)
    @{Number=1; Name="Landing_Page"; Path="index.php"; Folder="01_Common"; Description="Landing Page - System entry point"; LoginRequired=$false; Role=""},
    @{Number=2; Name="Login_Page"; Path="auth.php"; Folder="01_Common"; Description="Login Page - Unified authentication"; LoginRequired=$false; Role=""},
    
    # Admin Module (Admin login required)
    @{Number=3; Name="Admin_Dashboard"; Path="admin/dashboard.php"; Folder="02_Admin"; Description="Admin Dashboard - Statistics and overview"; LoginRequired=$true; Role="admin"},
    @{Number=4; Name="Manage_Students"; Path="admin/manage_students.php"; Folder="02_Admin"; Description="Manage Students - CRUD interface"; LoginRequired=$true; Role="admin"},
    @{Number=5; Name="Manage_Labs"; Path="admin/manage_labs.php"; Folder="02_Admin"; Description="Manage Labs - Lab configuration"; LoginRequired=$true; Role="admin"},
    @{Number=6; Name="Manage_Questions"; Path="admin/manage_questions.php"; Folder="02_Admin"; Description="Manage Questions - Question bank"; LoginRequired=$true; Role="admin"},
    @{Number=7; Name="Manage_Staff"; Path="admin/manage_staff.php"; Folder="02_Admin"; Description="Manage Staff - Staff management"; LoginRequired=$true; Role="admin"},
    @{Number=8; Name="Manage_Subjects"; Path="admin/manage_subjects.php"; Folder="02_Admin"; Description="Manage Subjects - Subject configuration"; LoginRequired=$true; Role="admin"},
    
    # Staff Module (Staff login required)
    @{Number=9; Name="Staff_Dashboard"; Path="staff/dashboard.php"; Folder="03_Staff"; Description="Staff Dashboard - Personal workspace"; LoginRequired=$true; Role="staff"},
    @{Number=10; Name="Start_Exam"; Path="staff/start_exam.php"; Folder="03_Staff"; Description="Start Exam - Session creation"; LoginRequired=$true; Role="staff"},
    @{Number=11; Name="Monitor_Exam"; Path="staff/monitor_exam.php"; Folder="03_Staff"; Description="Monitor Exam - Real-time grid view"; LoginRequired=$true; Role="staff"},
    @{Number=12; Name="Evaluate_Submissions"; Path="staff/evaluate.php"; Folder="03_Staff"; Description="Evaluate Submissions - Code evaluation"; LoginRequired=$true; Role="staff"},
    @{Number=13; Name="View_Results"; Path="staff/view_result.php"; Folder="03_Staff"; Description="View Results - Results summary"; LoginRequired=$true; Role="staff"},
    
    # Student Module (Student login required)
    @{Number=14; Name="Student_Dashboard"; Path="student/dashboard.php"; Folder="04_Student"; Description="Student Dashboard - Waiting room"; LoginRequired=$true; Role="student"},
    @{Number=15; Name="Exam_Interface"; Path="student/exam.php"; Folder="04_Student"; Description="Exam Interface - Questions and code editor"; LoginRequired=$true; Role="student"},
    @{Number=16; Name="Exam_History"; Path="student/history.php"; Folder="04_Student"; Description="Exam History - Past results"; LoginRequired=$true; Role="student"},
    @{Number=17; Name="Student_Profile"; Path="student/profile.php"; Folder="04_Student"; Description="Student Profile - Student information"; LoginRequired=$true; Role="student"},
    
    # Additional Pages
    @{Number=18; Name="View_Session_Details"; Path="admin/view_session_details.php"; Folder="05_Additional"; Description="View Session Details - Session overview"; LoginRequired=$true; Role="admin"},
    @{Number=19; Name="View_Submission"; Path="staff/view_submission.php"; Folder="05_Additional"; Description="View Submission - Individual code view"; LoginRequired=$true; Role="staff"},
    @{Number=20; Name="Reports"; Path="staff/reports.php"; Folder="05_Additional"; Description="Reports - Analytics dashboard"; LoginRequired=$true; Role="staff"}
)

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  SCREENSHOT CAPTURE TOOL" -ForegroundColor Cyan
Write-Host "  Lab Programming Allocation System" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

Write-Host "Base URL: $BaseUrl" -ForegroundColor Yellow
Write-Host "Output Folder: $OutputFolder" -ForegroundColor Yellow
Write-Host "Total Pages: $($screenshots.Count)`n" -ForegroundColor Yellow

# Check if Chrome/Edge is available
$chromePath = ""
if ($Browser -eq "chrome") {
    $chromePath = "C:\Program Files\Google\Chrome\Application\chrome.exe"
    if (-not (Test-Path $chromePath)) {
        $chromePath = "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"
    }
} elseif ($Browser -eq "edge") {
    $chromePath = "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"
}

if (-not (Test-Path $chromePath)) {
    Write-Host "ERROR: Browser not found at $chromePath" -ForegroundColor Red
    Write-Host "`nPlease install Chrome or Edge, or use manual capture method." -ForegroundColor Yellow
    Write-Host "`nMANUAL CAPTURE INSTRUCTIONS:" -ForegroundColor Cyan
    Write-Host "1. Open each URL in your browser" -ForegroundColor White
    Write-Host "2. Press F11 for fullscreen" -ForegroundColor White
    Write-Host "3. Press Windows + Shift + S for Snipping Tool" -ForegroundColor White
    Write-Host "4. Save with the naming convention shown below`n" -ForegroundColor White
    
    Write-Host "SCREENSHOT NAMING CONVENTION:" -ForegroundColor Cyan
    foreach ($shot in $screenshots) {
        $filename = "{0:D2}_{1}.png" -f $shot.Number, $shot.Name
        $fullPath = "$OutputFolder\$($shot.Folder)\$filename"
        Write-Host "  $fullPath" -ForegroundColor Gray
    }
    exit
}

# Method 1: Using Selenium WebDriver (if available)
Write-Host "Attempting automated capture with Selenium..." -ForegroundColor Yellow

try {
    # Check if Selenium module is installed
    $seleniumModule = Get-Module -ListAvailable -Name "Selenium" -ErrorAction SilentlyContinue
    
    if (-not $seleniumModule) {
        Write-Host "Selenium module not found. Installing..." -ForegroundColor Yellow
        Install-Module -Name "Selenium" -Scope CurrentUser -Force -ErrorAction Stop
    }
    
    Import-Module Selenium -ErrorAction Stop
    
    Write-Host "Starting browser..." -ForegroundColor Green
    $driver = Start-SeChrome -StartURL "about:blank" -Quiet
    $driver.Manage().Window.Maximize()
    
    # Function to perform login (tries multiple usernames)
    function Login-User {
        param($role)
        
        $cred = $credentials[$role]
        Write-Host "  Logging in as $role..." -ForegroundColor Yellow
        
        foreach ($username in $cred.usernames) {
            try {
                Write-Host "    Trying username: $username" -ForegroundColor Gray
                
                # Navigate to login page
                $driver.Navigate().GoToUrl("$BaseUrl/auth.php")
                Start-Sleep -Seconds 2
                
                # Find login form elements
                $credentialField = $null
                $passwordField = $null
                $submitButton = $null
                
                try {
                    $credentialField = $driver.FindElement([OpenQA.Selenium.By]::Name("credential"))
                    $passwordField = $driver.FindElement([OpenQA.Selenium.By]::Name("password"))
                    $submitButton = $driver.FindElement([OpenQA.Selenium.By]::CssSelector("button[type='submit'], input[type='submit']"))
                } catch {
                    # Try alternative selectors
                    $credentialField = $driver.FindElement([OpenQA.Selenium.By]::Id("credential"))
                    $passwordField = $driver.FindElement([OpenQA.Selenium.By]::Id("password"))
                    $submitButton = $driver.FindElement([OpenQA.Selenium.By]::CssSelector("form button, form input[type='submit']"))
                }
                
                # Clear and enter credentials
                $credentialField.Clear()
                $credentialField.SendKeys($username)
                Start-Sleep -Milliseconds 300
                
                $passwordField.Clear()
                $passwordField.SendKeys($cred.password)
                Start-Sleep -Milliseconds 300
                
                # Submit form
                $submitButton.Click()
                Start-Sleep -Seconds 4  # Wait for redirect
                
                # Check if login was successful (URL should change to dashboard)
                $currentUrl = $driver.Url
                if ($currentUrl -match "dashboard|exam|history|manage|start|monitor|evaluate|view|reports|profile") {
                    Write-Host "  ✓ Login successful with: $username" -ForegroundColor Green
                    return $true
                } else {
                    Write-Host "    ✗ Login failed with: $username (still on auth page)" -ForegroundColor Yellow
                    continue  # Try next username
                }
            }
            catch {
                Write-Host "    ✗ Error with $username : $($_.Exception.Message)" -ForegroundColor Yellow
                continue  # Try next username
            }
        }
        
        Write-Host "  ✗ All login attempts failed for role: $role" -ForegroundColor Red
        Write-Host "  Please check credentials in database or login manually" -ForegroundColor Yellow
        return $false
    }
    
    $captured = 0
    $failed = 0
    $currentRole = ""
    
    foreach ($shot in $screenshots) {
        $url = "$BaseUrl/$($shot.Path)"
        $filename = "{0:D2}_{1}.png" -f $shot.Number, $shot.Name
        $fullPath = "$OutputFolder\$($shot.Folder)\$filename"
        
        Write-Host "`n[$($shot.Number)/$($screenshots.Count)] Capturing: $($shot.Name)..." -ForegroundColor Cyan
        Write-Host "  URL: $url" -ForegroundColor Gray
        
        try {
            # Check if login is required and if we need to switch roles
            if ($shot.LoginRequired) {
                if ($currentRole -ne $shot.Role) {
                    # Need to login with different role
                    $loginSuccess = Login-User -role $shot.Role
                    if (-not $loginSuccess) {
                        Write-Host "  ✗ Skipping due to login failure" -ForegroundColor Red
                        $failed++
                        continue
                    }
                    $currentRole = $shot.Role
                }
            } else {
                # Public page, logout if logged in
                if ($currentRole -ne "") {
                    Write-Host "  Logging out for public page..." -ForegroundColor Yellow
                    $driver.Navigate().GoToUrl("$BaseUrl/auth.php?logout=1")
                    Start-Sleep -Seconds 2
                    $currentRole = ""
                }
            }
            
            # Navigate to page
            $driver.Navigate().GoToUrl($url)
            Start-Sleep -Seconds $DelaySeconds
            
            # Wait for page load
            $driver.Manage().Timeouts().ImplicitWait = [TimeSpan]::FromSeconds(5)
            
            # Additional wait for dynamic content
            Start-Sleep -Seconds 2
            
            # Take screenshot
            $screenshot = $driver.GetScreenshot()
            $screenshot.SaveAsFile($fullPath, [OpenQA.Selenium.ScreenshotImageFormat]::Png)
            
            Write-Host "  ✓ Saved: $fullPath" -ForegroundColor Green
            $captured++
        }
        catch {
            Write-Host "  ✗ Failed: $($_.Exception.Message)" -ForegroundColor Red
            $failed++
        }
    }
    
    $driver.Quit()
    
    Write-Host "`n========================================" -ForegroundColor Cyan
    Write-Host "  CAPTURE COMPLETE" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "Captured: $captured" -ForegroundColor Green
    Write-Host "Failed: $failed" -ForegroundColor $(if ($failed -gt 0) { "Red" } else { "Green" })
    Write-Host "`nScreenshots saved to: $OutputFolder" -ForegroundColor Yellow
}
catch {
    Write-Host "`nSelenium automation failed: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "`nFALLBACK: Manual capture instructions below`n" -ForegroundColor Yellow
    
    # Fallback: Generate URL list and instructions
    Write-Host "=" * 50 -ForegroundColor Cyan
    Write-Host "MANUAL CAPTURE GUIDE" -ForegroundColor Cyan
    Write-Host "=" * 50 -ForegroundColor Cyan
    
    $urlList = @()
    foreach ($shot in $screenshots) {
        $url = "$BaseUrl/$($shot.Path)"
        $filename = "{0:D2}_{1}.png" -f $shot.Number, $shot.Name
        $fullPath = "$OutputFolder\$($shot.Folder)\$filename"
        $urlList += [PSCustomObject]@{
            Number = $shot.Number
            Name = $shot.Name
            URL = $url
            SaveAs = $fullPath
        }
    }
    
    # Save URL list to CSV
    $urlList | Export-Csv -Path "$OutputFolder\screenshot_urls.csv" -NoTypeInformation
    Write-Host "`n✓ URL list saved to: $OutputFolder\screenshot_urls.csv" -ForegroundColor Green
    
    Write-Host "`nSTEPS TO CAPTURE:" -ForegroundColor Cyan
    Write-Host "1. Open the CSV file: $OutputFolder\screenshot_urls.csv" -ForegroundColor White
    Write-Host "2. For each row:" -ForegroundColor White
    Write-Host "   a. Open the URL in Chrome/Edge" -ForegroundColor White
    Write-Host "   b. Press F11 (fullscreen)" -ForegroundColor White
    Write-Host "   c. Press Windows + Shift + S (Snipping Tool)" -ForegroundColor White
    Write-Host "   d. Select full screen area" -ForegroundColor White
    Write-Host "   e. Save to the 'SaveAs' path shown in CSV" -ForegroundColor White
    Write-Host "`nOR use browser extension:" -ForegroundColor Yellow
    Write-Host "- Install 'Full Page Screen Capture' Chrome extension" -ForegroundColor White
    Write-Host "- Click extension icon on each page" -ForegroundColor White
    Write-Host "- Save with the filename from CSV`n" -ForegroundColor White
}
