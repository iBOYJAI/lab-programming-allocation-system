@echo off
REM ============================================================================
REM AUTOMATED SCREENSHOT CAPTURE WITH PUPPETEER (Node.js)
REM Lab Programming Allocation System
REM ============================================================================

echo ============================================================================
echo   AUTOMATED SCREENSHOT CAPTURE WITH PUPPETEER
echo   Lab Programming Allocation System
echo ============================================================================
echo.

REM Check if Node.js is installed
where node >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo ERROR: Node.js is not installed!
    echo.
    echo Please install Node.js from: https://nodejs.org/
    echo Then run this script again.
    echo.
    pause
    exit /b 1
)

echo Node.js found: 
node --version
echo.

REM Check if node_modules exists
if not exist "node_modules" (
    echo Installing dependencies (Puppeteer)...
    echo This may take a few minutes on first run...
    echo.
    call npm install
    if %ERRORLEVEL% NEQ 0 (
        echo.
        echo ERROR: Failed to install dependencies!
        echo Please check your internet connection and try again.
        echo.
        pause
        exit /b 1
    )
    echo.
)

REM Check if XAMPP is running (optional check)
echo Checking if server is accessible...
echo Make sure XAMPP (Apache + MySQL) is running!
echo.
timeout /t 2 /nobreak >nul

REM Run the capture script
echo Starting screenshot capture...
echo.
node capture-screenshots.js

echo.
echo ============================================================================
echo   CAPTURE COMPLETE!
echo ============================================================================
echo.
echo Check the folder: documentation\screenshots
echo.
pause
