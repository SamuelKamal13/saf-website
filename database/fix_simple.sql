-- ========================================
-- إصلاح الباركود - حل بسيط ومباشر
-- ========================================

USE attendance_app;

-- الخطوة 1: عرض الـ indexes الحالية على جدول events
SELECT 
    INDEX_NAME AS 'اسم الـ Index',
    NON_UNIQUE AS 'يسمح بالتكرار (1=نعم, 0=لا)',
    COLUMN_NAME AS 'العمود'
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = 'attendance_app'
    AND TABLE_NAME = 'events'
    AND COLUMN_NAME = 'barcode';

-- الخطوة 2: حذف الـ UNIQUE index (barcode)
-- إذا ظهر خطأ "check that column/key exists" - تجاهله، يعني أنه تم حذفه مسبقاً
ALTER TABLE events DROP INDEX barcode;

-- الخطوة 3: التأكد من وجود index عادي (غير UNIQUE)
-- بما أن idx_barcode موجود بالفعل، لا نحتاج لعمل شيء!

-- الخطوة 4: التحقق النهائي
SELECT 
    'تم الإصلاح! الباركود الآن يقبل القيم المكررة' AS Status,
    INDEX_NAME AS 'الـ Index الموجود',
    CASE 
        WHEN NON_UNIQUE = 1 THEN '✅ يسمح بالتكرار'
        ELSE '❌ لا يسمح بالتكرار (UNIQUE)'
    END AS 'الحالة'
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = 'attendance_app'
    AND TABLE_NAME = 'events'
    AND COLUMN_NAME = 'barcode';

-- ========================================
-- اختبار: حاول إضافة فعاليتين بنفس الباركود
-- ========================================

INSERT INTO events (name, type, date, barcode, description) VALUES
('اختبار 1', 'mass', '2025-10-13 09:00:00', 'TEST_BARCODE_001', 'اختبار الباركود المكرر'),
('اختبار 2', 'mass', '2025-10-20 09:00:00', 'TEST_BARCODE_001', 'اختبار الباركود المكرر');

SELECT 'إذا ظهرت هذه الرسالة بدون أخطاء، فالإصلاح نجح! ✅' AS Result;

-- تنظيف بيانات الاختبار
DELETE FROM events WHERE barcode = 'TEST_BARCODE_001';
