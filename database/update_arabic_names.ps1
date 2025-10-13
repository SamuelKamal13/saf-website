# ========================================
# Update Arabic Names in Database
# Date: October 6, 2025
# ========================================

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "تحديث الأسماء العربية في قاعدة البيانات" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if MySQL is running
$mysqlService = Get-Service -Name "MySQL" -ErrorAction SilentlyContinue
if ($null -eq $mysqlService) {
    Write-Host "❌ خدمة MySQL غير موجودة" -ForegroundColor Red
    Write-Host "يرجى التأكد من تشغيل XAMPP" -ForegroundColor Yellow
    pause
    exit
}

if ($mysqlService.Status -ne "Running") {
    Write-Host "❌ خدمة MySQL غير مشغلة" -ForegroundColor Red
    Write-Host "يرجى تشغيل MySQL من XAMPP Control Panel" -ForegroundColor Yellow
    pause
    exit
}

Write-Host "✅ MySQL قيد التشغيل" -ForegroundColor Green
Write-Host ""

# MySQL connection details
$mysqlPath = "D:\xampp\mysql\bin\mysql.exe"
$sqlFile = "$PSScriptRoot\update_arabic_names.sql"

# Check if mysql.exe exists
if (-not (Test-Path $mysqlPath)) {
    Write-Host "❌ لم يتم العثور على MySQL في: $mysqlPath" -ForegroundColor Red
    Write-Host "يرجى تحديث المسار في السكريبت" -ForegroundColor Yellow
    pause
    exit
}

# Check if SQL file exists
if (-not (Test-Path $sqlFile)) {
    Write-Host "❌ لم يتم العثور على ملف SQL: $sqlFile" -ForegroundColor Red
    pause
    exit
}

Write-Host "📝 تطبيق التحديثات..." -ForegroundColor Yellow
Write-Host ""

# Execute SQL file
try {
    & $mysqlPath -u root --default-character-set=utf8mb4 -e "source $sqlFile"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "✅ تم تحديث الأسماء العربية بنجاح!" -ForegroundColor Green
        Write-Host ""
        Write-Host "التحديثات المطبقة:" -ForegroundColor Cyan
        Write-Host "  • القداس الإلهي - باركود مشترك لجميع القداسات" -ForegroundColor White
        Write-Host "  • صلاة التسبحة - باركود مشترك لجميع صلوات التسبحة" -ForegroundColor White
        Write-Host "  • الاجتماع الروحي - باركود مشترك لجميع الاجتماعات" -ForegroundColor White
        Write-Host "  • النشاط الكنسي - باركود مشترك لجميع الأنشطة" -ForegroundColor White
        Write-Host ""
        Write-Host "يمكنك الآن:" -ForegroundColor Cyan
        Write-Host "  1. فتح لوحة التحكم Admin Panel" -ForegroundColor White
        Write-Host "  2. الانتقال إلى تبويب 'الباركودات المشتركة'" -ForegroundColor White
        Write-Host "  3. سترى الأسماء والأوصاف المحدثة بالعربية" -ForegroundColor White
    } else {
        Write-Host ""
        Write-Host "❌ فشل تطبيق التحديثات" -ForegroundColor Red
        Write-Host "يرجى التحقق من الأخطاء أعلاه" -ForegroundColor Yellow
    }
} catch {
    Write-Host ""
    Write-Host "❌ حدث خطأ: $_" -ForegroundColor Red
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
pause
