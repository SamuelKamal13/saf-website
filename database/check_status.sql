-- ========================================
-- التحقق من حالة جدول events
-- ========================================

USE attendance_app;

-- عرض جميع الـ indexes على جدول events
SELECT 
    INDEX_NAME AS 'اسم الـ Index',
    COLUMN_NAME AS 'العمود',
    NON_UNIQUE AS 'يسمح بالتكرار',
    CASE 
        WHEN NON_UNIQUE = 0 THEN '❌ UNIQUE (يرفض التكرار)'
        ELSE '✅ عادي (يسمح بالتكرار)'
    END AS 'النوع'
FROM 
    INFORMATION_SCHEMA.STATISTICS
WHERE 
    TABLE_SCHEMA = 'attendance_app'
    AND TABLE_NAME = 'events'
ORDER BY 
    INDEX_NAME, SEQ_IN_INDEX;

-- عرض بنية الجدول الكاملة
SHOW CREATE TABLE events;

-- ========================================
-- اختبار: هل يمكن إضافة فعاليات بنفس الباركود؟
-- ========================================

-- محاولة إضافة فعاليتين بنفس الباركود
INSERT INTO events (name, type, date, barcode, description) VALUES
('اختبار إصلاح الباركود 1', 'mass', '2025-10-13 09:00:00', 'TEST_FINAL_001', 'اختبار التكرار'),
('اختبار إصلاح الباركود 2', 'mass', '2025-10-20 09:00:00', 'TEST_FINAL_001', 'اختبار التكرار');

-- إذا نجح الاستعلام أعلاه، معناه الإصلاح مطبق بنجاح! ✅
SELECT 
    '✅ نجح! الباركود الآن يقبل القيم المكررة' AS الحالة,
    COUNT(*) AS 'عدد الفعاليات بنفس الباركود'
FROM 
    events 
WHERE 
    barcode = 'TEST_FINAL_001';

-- تنظيف بيانات الاختبار
DELETE FROM events WHERE barcode = 'TEST_FINAL_001';

SELECT '✅ الإصلاح مطبق بنجاح! يمكنك الآن استخدام نفس الباركود لفعاليات متعددة.' AS النتيجة;
