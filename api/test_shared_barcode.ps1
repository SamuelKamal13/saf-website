# Test Script for Shared Barcode Daily Attendance
# This script tests the fix for allowing daily scans with shared barcodes

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "اختبار الباركودات المشتركة" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$baseUrl = "http://192.168.1.10:8080/dashboard/attendance_api/api"

# Step 1: Login to get token
Write-Host "1. تسجيل الدخول..." -ForegroundColor Yellow
$loginBody = @{
    email = "admin@church.com"
    password = "admin123"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "$baseUrl/login.php" -Method POST -ContentType "application/json" -Body $loginBody
    
    if ($loginResponse.success) {
        $token = $loginResponse.data.token
        $userId = $loginResponse.data.user.id
        $userBarcodeId = $loginResponse.data.user.barcode_id
        Write-Host "   ✅ تم تسجيل الدخول بنجاح" -ForegroundColor Green
        Write-Host "   المستخدم: $($loginResponse.data.user.name)" -ForegroundColor White
        Write-Host "   Barcode ID: $userBarcodeId" -ForegroundColor White
        Write-Host ""
    } else {
        Write-Host "   ❌ فشل تسجيل الدخول: $($loginResponse.message)" -ForegroundColor Red
        exit
    }
} catch {
    Write-Host "   ❌ خطأ في الاتصال: $_" -ForegroundColor Red
    exit
}

# Step 2: Test scanning a shared barcode
Write-Host "2. اختبار مسح باركود مشترك (MASS_SHARED)..." -ForegroundColor Yellow

$attendanceBody = @{
    event_barcode = "MASS_SHARED"
    user_barcode_id = $userBarcodeId
} | ConvertTo-Json

$headers = @{
    "Authorization" = "Bearer $token"
    "Content-Type" = "application/json"
}

try {
    $attendanceResponse = Invoke-RestMethod -Uri "$baseUrl/attendance.php" -Method POST -Headers $headers -Body $attendanceBody
    
    if ($attendanceResponse.success) {
        Write-Host "   ✅ تم تسجيل الحضور بنجاح!" -ForegroundColor Green
        Write-Host "   الرسالة: $($attendanceResponse.message)" -ForegroundColor White
        Write-Host "   الفعالية: $($attendanceResponse.data.attendance.event_name)" -ForegroundColor White
        Write-Host "   التاريخ: $($attendanceResponse.data.attendance.timestamp)" -ForegroundColor White
        Write-Host "   الحالة: $($attendanceResponse.data.attendance.status)" -ForegroundColor White
        $eventId = $attendanceResponse.data.attendance.event_id
        Write-Host "   Event ID: $eventId" -ForegroundColor Gray
    } else {
        Write-Host "   ❌ فشل تسجيل الحضور: $($attendanceResponse.message)" -ForegroundColor Red
        Write-Host "   التفاصيل: $($attendanceResponse.data)" -ForegroundColor Gray
    }
} catch {
    $errorDetails = $_.ErrorDetails.Message | ConvertFrom-Json
    Write-Host "   ⚠️ استجابة من السيرفر:" -ForegroundColor Yellow
    Write-Host "   الرسالة: $($errorDetails.message)" -ForegroundColor White
    
    if ($errorDetails.message -like "*تم تسجيل حضورك بالفعل*") {
        Write-Host "   ℹ️ هذا صحيح - الحضور مسجل بالفعل اليوم" -ForegroundColor Cyan
    }
}

Write-Host ""

# Step 3: Try scanning again (should fail for same day)
Write-Host "3. محاولة المسح مرة أخرى (يجب أن يُرفض)..." -ForegroundColor Yellow
Start-Sleep -Seconds 1

try {
    $attendanceResponse2 = Invoke-RestMethod -Uri "$baseUrl/attendance.php" -Method POST -Headers $headers -Body $attendanceBody
    
    if ($attendanceResponse2.success) {
        Write-Host "   ⚠️ تم القبول (غير متوقع!)" -ForegroundColor Yellow
    } else {
        Write-Host "   ✅ تم الرفض كما هو متوقع" -ForegroundColor Green
        Write-Host "   الرسالة: $($attendanceResponse2.message)" -ForegroundColor White
    }
} catch {
    $errorDetails = $_.ErrorDetails.Message | ConvertFrom-Json
    if ($errorDetails.message -like "*تم تسجيل حضورك بالفعل*") {
        Write-Host "   ✅ تم الرفض بشكل صحيح - الحضور مسجل اليوم" -ForegroundColor Green
        Write-Host "   الرسالة: $($errorDetails.message)" -ForegroundColor White
        if ($errorDetails.data.message_ar) {
            Write-Host "   التفاصيل: $($errorDetails.data.message_ar)" -ForegroundColor White
        }
    } else {
        Write-Host "   ❌ خطأ غير متوقع: $($errorDetails.message)" -ForegroundColor Red
    }
}

Write-Host ""

# Step 4: Check attendance records
Write-Host "4. التحقق من سجلات الحضور..." -ForegroundColor Yellow

try {
    $historyResponse = Invoke-RestMethod -Uri "$baseUrl/get_attendance.php" -Method GET -Headers $headers
    
    if ($historyResponse.success) {
        $todayRecords = $historyResponse.data.attendance | Where-Object { 
            $_.timestamp -like "$(Get-Date -Format 'yyyy-MM-dd')*" 
        }
        
        Write-Host "   ✅ تم جلب السجلات" -ForegroundColor Green
        Write-Host "   إجمالي السجلات اليوم: $($todayRecords.Count)" -ForegroundColor White
        
        if ($todayRecords.Count -gt 0) {
            Write-Host ""
            Write-Host "   السجلات اليوم:" -ForegroundColor Cyan
            foreach ($record in $todayRecords) {
                Write-Host "   - $($record.event_name) | $($record.timestamp) | $($record.status)" -ForegroundColor White
            }
        }
    }
} catch {
    Write-Host "   ⚠️ لم يتم التحقق من السجلات" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ملاحظات مهمة:" -ForegroundColor Yellow
Write-Host "1. يجب أن ينجح المسح الأول في اليوم ✅" -ForegroundColor White
Write-Host "2. يجب أن يُرفض المسح الثاني في نفس اليوم ❌" -ForegroundColor White
Write-Host "3. يجب أن ينجح المسح في يوم جديد ✅" -ForegroundColor White
Write-Host ""
Write-Host "للاختبار في يوم جديد:" -ForegroundColor Cyan
Write-Host "- انتظر حتى منتصف الليل 🌙" -ForegroundColor White
Write-Host "- أو قم بتغيير تاريخ النظام مؤقتاً" -ForegroundColor White
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

pause
