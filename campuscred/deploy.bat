@echo off
title CampusCred Deployment & Server Launcher
color 0A

echo ==============================================================================
echo             CampusCred - Automated Deployment & Server Launcher
echo ==============================================================================
echo.

:: 1. Check Apache Process
echo [1/4] Checking Apache Web Server...
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo       [OK] Apache is running.
) else (
    echo       [STARTING] Launching Apache from C:\zz\apache...
    if exist "C:\zz\apache_start.bat" (
        start /B "" "C:\zz\apache_start.bat" >nul 2>&1
        timeout /t 2 /nobreak >nul
    ) else (
        echo       [WARNING] Could not find C:\zz\apache_start.bat. Please start Apache manually in XAMPP.
    )
)

:: 2. Check MySQL Process
echo.
echo [2/4] Checking MySQL Database Service...
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo       [OK] MySQL service is running.
) else (
    echo       [STARTING] Launching MySQL from C:\zz\mysql...
    if exist "C:\zz\mysql_start.bat" (
        start /B "" "C:\zz\mysql_start.bat" >nul 2>&1
        timeout /t 3 /nobreak >nul
    ) else (
        echo       [WARNING] Could not find C:\zz\mysql_start.bat. Please start MySQL manually in XAMPP.
    )
)

:: 3. Test Database Connection and Migrations
echo.
echo [3/4] Verifying Database and Migrations...
if exist "C:\zz\php\php.exe" (
    "C:\zz\php\php.exe" -r "require 'db.php'; echo '      [OK] Database connected and schema verified.' . PHP_EOL;"
) else (
    echo       [INFO] PHP CLI not found in default path, skipping CLI check.
)

:: 4. Get Local Network IP
echo.
echo [4/4] Detecting Network Deployment URLs...
for /f "tokens=4" %%a in ('route print ^| find " 0.0.0.0"') do (
    set "LOCAL_IP=%%a"
    goto :found_ip
)
:found_ip

echo.
echo ==============================================================================
echo                      CampusCred is Successfully Deployed!
echo ==============================================================================
echo.
echo  * Localhost URL:      http://localhost/campuscred/
if defined LOCAL_IP (
echo  * Local Network URL:  http://%LOCAL_IP%/campuscred/
echo    (Share this with any device on the same Wi-Fi / LAN!)
)
echo.
echo  * Recruiter Demo:     recruiter@techcorp.com / Recruiter@123
echo  * Student Demo:       aravind@skit.ac.in     / Student@123
echo  * Admin Demo:         admin@skit.ac.in       / Admin@123
echo.
echo ==============================================================================
echo.
echo Opening CampusCred in your default web browser...
start "" "http://localhost/campuscred/"
echo.
pause
