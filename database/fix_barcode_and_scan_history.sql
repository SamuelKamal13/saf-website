-- ========================================
-- تحديث جدول events
-- التاريخ: 6 أكتوبر 2025
-- ========================================

USE attendance_app;

-- إزالة قيد UNIQUE من barcode للسماح باستخدام نفس الباركود أكثر من مرة
-- هذا يسمح بتسجيل الحضور المتكرر (مثل: قداس كل أسبوع بنفس الباركود)

-- محاولة حذف index barcode إذا كان UNIQUE
ALTER TABLE events DROP INDEX barcode;

-- إضافة index عادي فقط إذا لم يكن موجوداً
-- إذا ظهر خطأ "Duplicate key name" تجاهله - المعنى أن الـ index موجود بالفعل
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
               WHERE table_schema = 'attendance_app' 
               AND table_name = 'events' 
               AND index_name = 'idx_barcode');

SET @sqlstmt := IF(@exist > 0, 
                   'SELECT "Index idx_barcode already exists" AS Info', 
                   'ALTER TABLE events ADD INDEX idx_barcode (barcode)');

PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ملاحظة: عمود created_by غير موجود في schema.sql الأصلي
-- إذا كنت تريد تتبع من أنشأ الفعالية، يمكنك إضافته:
-- ALTER TABLE events ADD COLUMN created_by INT NULL AFTER location;
-- ALTER TABLE events ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- ========================================
-- جدول attendance يسجل تلقائياً تاريخ ووقت المسح
-- ========================================
-- عمود timestamp في جدول attendance يسجل تلقائياً متى تم المسح
-- هذا يعني:
-- 1. المستخدم يمسح الباركود
-- 2. يتم إنشاء سجل في attendance مع user_id, event_id
-- 3. يتم تسجيل timestamp تلقائياً (التاريخ والوقت الحالي)
-- 4. يمكن للمستخدم مسح نفس الباركود لفعاليات مختلفة

-- تحقق من البنية الحالية
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_KEY,
    EXTRA
FROM 
    INFORMATION_SCHEMA.COLUMNS
WHERE 
    TABLE_SCHEMA = 'attendance_app' 
    AND TABLE_NAME = 'events'
ORDER BY 
    ORDINAL_POSITION;

-- عرض جميع المؤشرات (indexes) على جدول events
SHOW INDEX FROM events;

-- ========================================
-- أمثلة على الاستخدام
-- ========================================

-- مثال 1: إنشاء فعالية بباركود
INSERT INTO events (name, type, date, barcode, description, location) 
VALUES ('قداس الأحد', 'mass', '2025-10-12 09:00:00', 'MASS_001', 'قداس القديس مارمرقس', 'الكنيسة الرئيسية');

-- مثال 2: إنشاء فعالية أخرى بنفس الباركود (مسموح الآن)
INSERT INTO events (name, type, date, barcode, description, location) 
VALUES ('قداس الأحد التالي', 'mass', '2025-10-19 09:00:00', 'MASS_001', 'قداس القديس مارمرقس', 'الكنيسة الرئيسية');

-- مثال 3: تسجيل حضور (timestamp يسجل تلقائياً)
INSERT INTO attendance (user_id, event_id, status) 
VALUES (1, 1, 'present');
-- timestamp سيتم تسجيله تلقائياً = الآن

-- مثال 4: عرض تاريخ المسح لمستخدم معين
SELECT 
    u.name AS 'اسم المستخدم',
    e.name AS 'اسم الفعالية',
    e.date AS 'تاريخ الفعالية',
    a.timestamp AS 'وقت المسح',
    a.status AS 'الحالة'
FROM 
    attendance a
    JOIN users u ON a.user_id = u.id
    JOIN events e ON a.event_id = e.id
WHERE 
    u.id = 1
ORDER BY 
    a.timestamp DESC;

-- مثال 5: عرض جميع من مسحوا باركود معين
SELECT 
    e.barcode AS 'الباركود',
    e.name AS 'اسم الفعالية',
    u.name AS 'اسم المستخدم',
    a.timestamp AS 'وقت المسح',
    DATE_FORMAT(a.timestamp, '%Y-%m-%d %H:%i:%s') AS 'التاريخ والوقت'
FROM 
    attendance a
    JOIN users u ON a.user_id = u.id
    JOIN events e ON a.event_id = e.id
WHERE 
    e.barcode = 'MASS_001'
ORDER BY 
    a.timestamp DESC;

-- ========================================
-- التحقق من التطبيق
-- ========================================

-- 1. تحقق من أن barcode لم يعد UNIQUE
SHOW CREATE TABLE events;

-- 2. جرب إضافة فعاليتين بنفس الباركود
-- يجب أن يعمل بدون أخطاء

-- 3. تحقق من أن timestamp يسجل تلقائياً في attendance
DESC attendance;
