@echo off
REM Attendance Tracker - Database Setup Script
REM Simple batch file to import database

echo ========================================
echo Attendance Tracker - Database Setup
echo ========================================
echo.

REM Check if XAMPP exists
if not exist "C:\xampp\mysql\bin\mysql.exe" (
    echo ERROR: XAMPP MySQL not found!
    echo Please install XAMPP or update the path in this script.
    echo.
    echo Alternative: Use phpMyAdmin
    echo 1. Open http://localhost/phpmyadmin
    echo 2. Click Import tab
    echo 3. Choose file: schema.sql
    echo 4. Click Go
    pause
    exit /b
)

echo Found: C:\xampp\mysql\bin\mysql.exe
echo.

echo Importing database...
echo.

REM Import database (default XAMPP has no password)
"C:\xampp\mysql\bin\mysql.exe" -u root < "%~dp0schema.sql"

if %errorlevel% equ 0 (
    echo.
    echo SUCCESS! Database imported successfully!
    echo.
    echo Database: attendance_app
    echo Default Admin:
    echo   Email: admin@church.com
    echo   Password: admin123
    echo.
    echo Next steps:
    echo 1. Copy 'api' folder to C:\xampp\htdocs\attendance_api\
    echo 2. Start Apache and MySQL in XAMPP Control Panel
    echo 3. Test: http://localhost/attendance_api/api/get_announcements.php
    echo 4. Run: flutter run
) else (
    echo.
    echo ERROR: Failed to import database!
    echo.
    echo Try using phpMyAdmin instead:
    echo 1. Open http://localhost/phpmyadmin
    echo 2. Click Import tab
    echo 3. Choose file: %~dp0schema.sql
    echo 4. Click Go
)

echo.
pause
