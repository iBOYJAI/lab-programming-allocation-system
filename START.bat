@echo off
setlocal enabledelayedexpansion
title LAB ALLOCATION SYSTEM - AUTO SETUP & RUN
color 0B

:: ==========================================
:: 1. DYNAMIC CONFIGURATION
:: ==========================================

:: Get the current folder name (e.g., "LAB PROGRAMMING ALLOCATION SYSTEM")
for %%I in (.) do set "CURRENT_DIR_NAME=%%~nxI"

:: Handle spaces in URL (Replace space with %20)
set "URL_PATH=!CURRENT_DIR_NAME: =%%20!"
set "APP_URL=http://localhost/!URL_PATH!"

set "DB_NAME=lab_allocation_system"
set "SQL_FILE=database\lab_allocation_system.sql"

cls
echo  =============================================================
echo    LAB PROGRAMMING ALLOCATION SYSTEM
echo    [Portable Auto-Setup & Launcher]
echo  =============================================================
echo.
echo  [INFO] Detected App URL: !APP_URL!
echo.

:: ==========================================
:: 2. DETECT MYSQL EXECUTABLE
:: ==========================================
echo  [SYSTEM] Searching for MySQL...

:: Check system PATH first
where mysql >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    set "MYSQL_CMD=mysql"
    echo         - Found in System PATH.
) else (
    :: Check common XAMPP locations
    if exist "C:\xampp\mysql\bin\mysql.exe" (
        set "MYSQL_CMD=C:\xampp\mysql\bin\mysql.exe"
        echo         - Found in C:\xampp
    ) else if exist "D:\xampp\mysql\bin\mysql.exe" (
        set "MYSQL_CMD=D:\xampp\mysql\bin\mysql.exe"
        echo         - Found in D:\xampp
    ) else if exist "E:\xampp\mysql\bin\mysql.exe" (
        set "MYSQL_CMD=E:\xampp\mysql\bin\mysql.exe"
        echo         - Found in E:\xampp
    ) else (
        echo  [ERROR] MySQL not found! 
        echo          Please ensure XAMPP is installed and running.
        echo.
        pause
        exit /b
    )
)

:: ==========================================
:: 3. GENERATE FRESH DATA (Optional)
:: ==========================================
:: If Python is available, we generate fresh usage data. 
:: If not, we use the existing SQL file.

python --version >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    echo.
    echo  [SEED] Python detected. Generating fresh seed data...
    python generate_seed.py
    if !ERRORLEVEL! EQU 0 (
        echo         - Seed generation successful.
    ) else (
        echo         - [WARNING] Seed generation failed. Using existing SQL file.
    )
) else (
    echo.
    echo  [INFO] Python not found. Skipping seed generation.
    echo         Using existing 'database\lab_allocation_system.sql'.
)

:: ==========================================
:: 4. IMPORT DATABASE
:: ==========================================
echo.
echo  [DATABASE] Setting up database '!DB_NAME!'...

if exist "!SQL_FILE!" (
    :: Import command: mysql -u root < file.sql
    :: We use cmd /c to ensure redirection works properly with full paths
    
    cmd /c ""!MYSQL_CMD!" -u root < "!SQL_FILE!""
    
    if !ERRORLEVEL! EQU 0 (
        echo         - Database imported successfully.
    ) else (
        echo         - [ERROR] Import failed. Is MySQL running?
    )
) else (
    echo  [ERROR] SQL file not found: !SQL_FILE!
    echo          Cannot proceed without database file.
    pause
    exit /b
)

:: ==========================================
:: 5. LAUNCH APPLICATION
:: ==========================================
echo.
echo  =============================================================
echo    LAUNCHING BROWSER...
echo  =============================================================
echo.

start "" "!APP_URL!"

echo  [SUCCESS] Application is running.
echo  You can close this window.
timeout /t 10
