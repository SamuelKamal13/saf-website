@echo off
chcp 65001 >nul
echo ========================================
echo تحديث الأسماء العربية في قاعدة البيانات
echo ========================================
echo.

D:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 -e "source D:\Projects\osret_elkdes_oghostinos\database\update_arabic_names.sql"

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo تم التحديث بنجاح!
    echo ========================================
    echo.
    echo يمكنك الآن فتح لوحة التحكم والتحقق من التحديثات
    echo http://192.168.1.10:8080/dashboard/attendance_api/admin/
    echo.
) else (
    echo.
    echo فشل التحديث!
    echo.
)

pause
