-- ========================================
-- Update Arabic Names and Descriptions
-- Date: October 6, 2025
-- Purpose: Update event_type_barcodes with clear Arabic names and descriptions
-- ========================================

USE attendance_app;

-- ========================================
-- Update event_type_barcodes with clear Arabic content
-- ========================================

UPDATE event_type_barcodes 
SET 
    arabic_name = 'القداس الإلهي',
    description = 'باركود مشترك لجميع القداسات - يستخدم لتسجيل الحضور في أي قداس'
WHERE event_type = 'mass';

UPDATE event_type_barcodes 
SET 
    arabic_name = 'صلاة التسبحة',
    description = 'باركود مشترك لجميع صلوات التسبحة - يستخدم لتسجيل الحضور في أي تسبحة'
WHERE event_type = 'tasbeha';

UPDATE event_type_barcodes 
SET 
    arabic_name = 'الاجتماع الروحي',
    description = 'باركود مشترك لجميع الاجتماعات - يستخدم لتسجيل الحضور في أي اجتماع'
WHERE event_type = 'meeting';

UPDATE event_type_barcodes 
SET 
    arabic_name = 'النشاط الكنسي',
    description = 'باركود مشترك لجميع الأنشطة - يستخدم لتسجيل الحضور في أي نشاط'
WHERE event_type = 'activity';

-- ========================================
-- Verification: Show updated data
-- ========================================

SELECT 
    event_type AS 'نوع الفعالية',
    arabic_name AS 'الاسم العربي',
    description AS 'الوصف',
    barcode AS 'الباركود',
    is_active AS 'نشط'
FROM event_type_barcodes
ORDER BY 
    CASE event_type
        WHEN 'mass' THEN 1
        WHEN 'tasbeha' THEN 2
        WHEN 'meeting' THEN 3
        WHEN 'activity' THEN 4
    END;

-- ========================================
-- End of Update
-- ========================================
