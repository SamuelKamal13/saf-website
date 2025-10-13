-- ========================================
-- اختبار الباركود المتكرر وتاريخ المسح
-- ========================================

USE attendance_app;

-- ========================================
-- اختبار 1: إنشاء فعاليات متعددة بنفس الباركود
-- ========================================

-- قداس كل أحد بنفس الباركود
INSERT INTO events (name, type, date, barcode, description, location) VALUES
('قداس الأحد - 13 أكتوبر', 'mass', '2025-10-13 09:00:00', 'SUNDAY_MASS', 'قداس الأحد الصباحي', 'الكنيسة الكبرى'),
('قداس الأحد - 20 أكتوبر', 'mass', '2025-10-20 09:00:00', 'SUNDAY_MASS', 'قداس الأحد الصباحي', 'الكنيسة الكبرى'),
('قداس الأحد - 27 أكتوبر', 'mass', '2025-10-27 09:00:00', 'SUNDAY_MASS', 'قداس الأحد الصباحي', 'الكنيسة الكبرى');

-- تسبحة كل جمعة بنفس الباركود
INSERT INTO events (name, type, date, barcode, description, location) VALUES
('تسبحة الجمعة - 11 أكتوبر', 'tasbeha', '2025-10-11 19:00:00', 'FRIDAY_TASBEHA', 'تسبحة نصف الليل', 'الكنيسة الكبرى'),
('تسبحة الجمعة - 18 أكتوبر', 'tasbeha', '2025-10-18 19:00:00', 'FRIDAY_TASBEHA', 'تسبحة نصف الليل', 'الكنيسة الكبرى'),
('تسبحة الجمعة - 25 أكتوبر', 'tasbeha', '2025-10-25 19:00:00', 'FRIDAY_TASBEHA', 'تسبحة نصف الليل', 'الكنيسة الكبرى');

SELECT '✅ تم إنشاء 6 فعاليات بباركودين متكررين' AS Result;

-- ========================================
-- اختبار 2: محاكاة مسح الباركود من قبل مستخدمين
-- ========================================

-- افترض أن لدينا 3 مستخدمين (تأكد من وجودهم في جدول users)
-- إذا لم يوجدوا، قم بإنشائهم:

-- INSERT INTO users (name, email, password, role, barcode_id, phone) VALUES
-- ('مينا جورج', 'mina@test.com', MD5('123456'), 'attendee', 'USER_001', '01234567890'),
-- ('مريم سمير', 'mariam@test.com', MD5('123456'), 'attendee', 'USER_002', '01234567891'),
-- ('بولا ميشيل', 'paula@test.com', MD5('123456'), 'attendee', 'USER_003', '01234567892');

-- محاكاة مسح الباركود: مستخدمين يحضرون قداس 13 أكتوبر
INSERT INTO attendance (user_id, event_id, status, timestamp) VALUES
(1, (SELECT id FROM events WHERE name = 'قداس الأحد - 13 أكتوبر' LIMIT 1), 'present', '2025-10-13 09:05:00'),
(2, (SELECT id FROM events WHERE name = 'قداس الأحد - 13 أكتوبر' LIMIT 1), 'present', '2025-10-13 09:08:00'),
(3, (SELECT id FROM events WHERE name = 'قداس الأحد - 13 أكتوبر' LIMIT 1), 'present', '2025-10-13 09:12:00');

-- محاكاة مسح الباركود: مستخدمين يحضرون قداس 20 أكتوبر
INSERT INTO attendance (user_id, event_id, status, timestamp) VALUES
(1, (SELECT id FROM events WHERE name = 'قداس الأحد - 20 أكتوبر' LIMIT 1), 'present', '2025-10-20 09:03:00'),
(2, (SELECT id FROM events WHERE name = 'قداس الأحد - 20 أكتوبر' LIMIT 1), 'present', '2025-10-20 09:10:00');

-- محاكاة مسح الباركود: تسبحة الجمعة
INSERT INTO attendance (user_id, event_id, status, timestamp) VALUES
(1, (SELECT id FROM events WHERE name = 'تسبحة الجمعة - 11 أكتوبر' LIMIT 1), 'present', '2025-10-11 19:02:00'),
(3, (SELECT id FROM events WHERE name = 'تسبحة الجمعة - 11 أكتوبر' LIMIT 1), 'present', '2025-10-11 19:05:00');

SELECT '✅ تم محاكاة 7 عمليات مسح للباركود' AS Result;

-- ========================================
-- اختبار 3: عرض النتائج
-- ========================================

-- عرض جميع الفعاليات بباركود SUNDAY_MASS
SELECT 
    '=== فعاليات قداس الأحد ===' AS Info,
    id AS 'رقم الفعالية',
    name AS 'اسم الفعالية',
    DATE_FORMAT(date, '%Y-%m-%d %H:%i') AS 'التاريخ',
    barcode AS 'الباركود'
FROM 
    events 
WHERE 
    barcode = 'SUNDAY_MASS'
ORDER BY 
    date;

-- عرض تاريخ حضور المستخدم رقم 1 (مينا)
SELECT 
    '=== تاريخ حضور المستخدم 1 ===' AS Info,
    u.name AS 'المستخدم',
    e.name AS 'الفعالية',
    e.barcode AS 'الباركود',
    DATE_FORMAT(e.date, '%Y-%m-%d %H:%i') AS 'تاريخ الفعالية',
    DATE_FORMAT(a.timestamp, '%Y-%m-%d %H:%i:%s') AS 'وقت المسح',
    TIMESTAMPDIFF(MINUTE, e.date, a.timestamp) AS 'الفرق (دقائق)',
    a.status AS 'الحالة'
FROM 
    attendance a
    JOIN users u ON a.user_id = u.id
    JOIN events e ON a.event_id = e.id
WHERE 
    u.id = 1
ORDER BY 
    a.timestamp DESC;

-- إحصائيات الحضور حسب الباركود
SELECT 
    '=== إحصائيات حسب الباركود ===' AS Info,
    e.barcode AS 'الباركود',
    COUNT(DISTINCT e.id) AS 'عدد الفعاليات',
    COUNT(DISTINCT a.user_id) AS 'عدد الحضور الفريد',
    COUNT(a.id) AS 'إجمالي عمليات المسح'
FROM 
    events e
    LEFT JOIN attendance a ON e.id = a.event_id
WHERE 
    e.barcode IN ('SUNDAY_MASS', 'FRIDAY_TASBEHA')
GROUP BY 
    e.barcode;

-- عرض جميع عمليات المسح مرتبة حسب الوقت
SELECT 
    '=== جميع عمليات المسح ===' AS Info,
    u.name AS 'المستخدم',
    e.barcode AS 'الباركود',
    e.name AS 'الفعالية',
    DATE_FORMAT(a.timestamp, '%Y-%m-%d %H:%i:%s') AS 'وقت المسح',
    a.status AS 'الحالة'
FROM 
    attendance a
    JOIN users u ON a.user_id = u.id
    JOIN events e ON a.event_id = e.id
ORDER BY 
    a.timestamp DESC;

-- ========================================
-- اختبار 4: اختبار القيود
-- ========================================

-- محاولة تسجيل نفس المستخدم مرتين في نفس الفعالية (يجب أن يفشل)
-- هذا محمي بـ UNIQUE KEY (user_id, event_id)
-- SELECT 'محاولة تسجيل مكرر (يجب أن يفشل)...' AS Test;
-- INSERT INTO attendance (user_id, event_id, status, timestamp) VALUES
-- (1, (SELECT id FROM events WHERE name = 'قداس الأحد - 13 أكتوبر' LIMIT 1), 'present', NOW());
-- إذا ظهر خطأ "Duplicate entry" - هذا صحيح!

-- ========================================
-- التنظيف (اختياري - لحذف البيانات التجريبية)
-- ========================================

-- DELETE FROM attendance WHERE notes IS NULL AND event_id IN (SELECT id FROM events WHERE barcode IN ('SUNDAY_MASS', 'FRIDAY_TASBEHA'));
-- DELETE FROM events WHERE barcode IN ('SUNDAY_MASS', 'FRIDAY_TASBEHA');

SELECT '✅ اكتمل الاختبار! راجع النتائج في الأعلى.' AS FinalResult;
