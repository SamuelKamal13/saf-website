# Attendance Tracker - Database Setup Script for Windows
# Run this script with PowerShell

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Attendance Tracker - Database Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if XAMPP is installed
$xamppPath = "C:\xampp"
if (-not (Test-Path $xamppPath)) {
    Write-Host "ERROR: XAMPP not found at C:\xampp" -ForegroundColor Red
    Write-Host "Please install XAMPP first or update the path in this script" -ForegroundColor Yellow
    Write-Host "Download from: https://www.apachefriends.org/download.html" -ForegroundColor Yellow
    exit
}

Write-Host "✓ XAMPP found at $xamppPath" -ForegroundColor Green

# Check if MySQL executable exists
$mysqlExe = "$xamppPath\mysql\bin\mysql.exe"
if (-not (Test-Path $mysqlExe)) {
    Write-Host "ERROR: MySQL executable not found at $mysqlExe" -ForegroundColor Red
    exit
}

Write-Host "✓ MySQL executable found" -ForegroundColor Green
Write-Host ""

# Get the schema file path
$schemaPath = Join-Path $PSScriptRoot "schema.sql"
if (-not (Test-Path $schemaPath)) {
    Write-Host "ERROR: schema.sql not found at $schemaPath" -ForegroundColor Red
    exit
}

Write-Host "✓ Schema file found" -ForegroundColor Green
Write-Host ""

# Prompt for MySQL password
Write-Host "Enter MySQL root password (press Enter if no password):" -ForegroundColor Yellow
$password = Read-Host -AsSecureString
$plainPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto([System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($password))

Write-Host ""
Write-Host "Importing database..." -ForegroundColor Yellow

try {
    if ($plainPassword -eq "") {
        # No password
        & $mysqlExe -u root < $schemaPath 2>&1 | Out-Null
    } else {
        # With password
        & $mysqlExe -u root "-p$plainPassword" < $schemaPath 2>&1 | Out-Null
    }
    
    Write-Host "✓ Database imported successfully!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Database: attendance_app" -ForegroundColor Cyan
    Write-Host "Default Admin:" -ForegroundColor Cyan
    Write-Host "  Email: admin@church.com" -ForegroundColor White
    Write-Host "  Password: admin123" -ForegroundColor White
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. Copy 'api' folder to C:\xampp\htdocs\attendance_api\" -ForegroundColor White
    Write-Host "2. Start Apache and MySQL in XAMPP Control Panel" -ForegroundColor White
    Write-Host "3. Test API: http://localhost/attendance_api/api/get_announcements.php" -ForegroundColor White
    Write-Host "4. Run Flutter app: flutter run" -ForegroundColor White
    
} catch {
    Write-Host "ERROR: Failed to import database" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host ""
    Write-Host "Alternative: Use phpMyAdmin" -ForegroundColor Yellow
    Write-Host "1. Open http://localhost/phpmyadmin" -ForegroundColor White
    Write-Host "2. Click Import tab" -ForegroundColor White
    Write-Host "3. Choose file: $schemaPath" -ForegroundColor White
    Write-Host "4. Click Go" -ForegroundColor White
}

Write-Host ""
Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
