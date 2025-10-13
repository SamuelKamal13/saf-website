# Test Shared Barcode - Simplified
$baseUrl = "http://192.168.1.10:8080/dashboard/attendance_api/api"

Write-Host "Testing Shared Barcode System..." -ForegroundColor Cyan
Write-Host ""

# Login
Write-Host "1. Login..." -ForegroundColor Yellow
$loginBody = @{
    email = "admin@church.com"
    password = "admin123"
} | ConvertTo-Json

$loginResponse = Invoke-RestMethod -Uri "$baseUrl/login.php" -Method POST -ContentType "application/json" -Body $loginBody
$token = $loginResponse.data.token
$userBarcodeId = $loginResponse.data.user.barcode_id

Write-Host "   Success! User: $($loginResponse.data.user.name)" -ForegroundColor Green
Write-Host ""

# Test attendance
Write-Host "2. Scanning MASS_SHARED..." -ForegroundColor Yellow

$attendanceBody = @{
    event_barcode = "MASS_SHARED"
    user_barcode_id = $userBarcodeId
} | ConvertTo-Json

$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
}

try {
    $response = Invoke-RestMethod -Uri "$baseUrl/attendance.php" -Method POST -Headers $headers -Body $attendanceBody
    
    Write-Host "   SUCCESS!" -ForegroundColor Green
    Write-Host "   Message: $($response.message)" -ForegroundColor White
    Write-Host "   Event: $($response.data.attendance.event_name)" -ForegroundColor White
    Write-Host "   Time: $($response.data.attendance.timestamp)" -ForegroundColor White
    Write-Host ""
} catch {
    $error = $_.ErrorDetails.Message | ConvertFrom-Json
    Write-Host "   FAILED!" -ForegroundColor Red
    Write-Host "   Message: $($error.message)" -ForegroundColor White
    
    if ($error.data.message_ar) {
        Write-Host "   Arabic: $($error.data.message_ar)" -ForegroundColor White
    }
    Write-Host ""
}

# Try again
Write-Host "3. Scanning again (should fail)..." -ForegroundColor Yellow
Start-Sleep -Seconds 1

try {
    $response2 = Invoke-RestMethod -Uri "$baseUrl/attendance.php" -Method POST -Headers $headers -Body $attendanceBody
    Write-Host "   UNEXPECTED: Accepted second scan!" -ForegroundColor Yellow
} catch {
    $error = $_.ErrorDetails.Message | ConvertFrom-Json
    Write-Host "   CORRECT: Rejected duplicate" -ForegroundColor Green
    Write-Host "   Message: $($error.message)" -ForegroundColor White
}

Write-Host ""
Write-Host "Test completed!" -ForegroundColor Cyan
pause
