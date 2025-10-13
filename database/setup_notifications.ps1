# Apply Notifications Migration Script
# This script creates the notifications tables in the database

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "Notifications System Migration" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Set your MySQL credentials here
$MYSQL_USER = "root"
$MYSQL_PASS = ""
$MYSQL_DB = "attendance_app"
$MYSQL_HOST = "localhost"

Write-Host "Applying migration to database: $MYSQL_DB" -ForegroundColor Yellow
Write-Host ""

# Build MySQL command
$mysqlCmd = "mysql"
$mysqlArgs = @(
    "-u", $MYSQL_USER,
    "-h", $MYSQL_HOST,
    $MYSQL_DB
)

if ($MYSQL_PASS -ne "") {
    $mysqlArgs = @("-u", $MYSQL_USER, "-p$MYSQL_PASS", "-h", $MYSQL_HOST, $MYSQL_DB)
}

try {
    # Apply the migration
    Get-Content "migration_notifications.sql" | & $mysqlCmd $mysqlArgs
    
    Write-Host ""
    Write-Host "===================================" -ForegroundColor Green
    Write-Host "✅ Migration applied successfully!" -ForegroundColor Green
    Write-Host "===================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. Setup cron job for scheduled notifications" -ForegroundColor White
    Write-Host "2. Test the notification system from admin panel" -ForegroundColor White
    Write-Host ""
    
} catch {
    Write-Host ""
    Write-Host "===================================" -ForegroundColor Red
    Write-Host "❌ ERROR: Migration failed!" -ForegroundColor Red
    Write-Host "===================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Error details: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please check your MySQL credentials and try again." -ForegroundColor Yellow
    Write-Host ""
}

Write-Host "Press any key to continue..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
