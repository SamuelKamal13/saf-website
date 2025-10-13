@echo off
REM Apply Notifications Migration Script
REM This script creates the notifications tables in the database

echo =====================================
echo Notifications System Migration
echo =====================================
echo.

REM Set your MySQL credentials here
set MYSQL_USER=root
set MYSQL_PASS=
set MYSQL_DB=attendance_app
set MYSQL_HOST=localhost

echo Applying migration to database: %MYSQL_DB%
echo.

REM Apply the migration
mysql -u %MYSQL_USER% -p%MYSQL_PASS% -h %MYSQL_HOST% %MYSQL_DB% < migration_notifications.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ===================================
    echo Migration applied successfully!
    echo ===================================
    echo.
    echo Next steps:
    echo 1. Setup cron job for scheduled notifications
    echo 2. Test the notification system from admin panel
    echo.
) else (
    echo.
    echo ===================================
    echo ERROR: Migration failed!
    echo ===================================
    echo Please check your MySQL credentials and try again.
    echo.
)

pause
