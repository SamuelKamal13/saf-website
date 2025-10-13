-- ========================================
-- Migration: Add Reflection Categories Management
-- Date: 2025-10-07
-- Purpose: إضافة نظام إدارة تصنيفات التأملات الروحية
-- ========================================

-- إنشاء جدول تصنيفات التأملات
CREATE TABLE IF NOT EXISTS reflection_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_ar VARCHAR(100) NOT NULL UNIQUE COMMENT 'الاسم بالعربية',
    name_en VARCHAR(100) NOT NULL UNIQUE COMMENT 'الاسم بالإنجليزية',
    description TEXT COMMENT 'وصف التصنيف',
    icon VARCHAR(50) COMMENT 'أيقونة التصنيف (emoji أو اسم أيقونة)',
    color VARCHAR(7) DEFAULT '#8B0000' COMMENT 'لون التصنيف (hex)',
    display_order INT DEFAULT 0 COMMENT 'ترتيب العرض',
    is_active TINYINT(1) DEFAULT 1 COMMENT 'هل التصنيف نشط',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة التصنيفات الافتراضية
INSERT INTO reflection_categories (name_ar, name_en, description, icon, color, display_order, is_active) VALUES
('الصلاة', 'Prayer', 'تأملات وتعاليم عن الصلاة والحياة الصلائية', '🙏', '#8B0000', 1, 1),
('دراسة الكتاب المقدس', 'Bible Study', 'تأملات في نصوص الكتاب المقدس وشروحاته', '📖', '#D4AF37', 2, 1),
('القديسين', 'Saints', 'سير وتعاليم القديسين والآباء', '✨', '#800020', 3, 1),
('الروحانية', 'Spirituality', 'تأملات في الحياة الروحية والنمو الروحي', '💫', '#4B0082', 4, 1),
('الأسرة', 'Family', 'تأملات عن الأسرة المسيحية والعلاقات الأسرية', '👨‍👩‍👧‍👦', '#2E8B57', 5, 1),
('الشباب', 'Youth', 'تأملات موجهة للشباب والفتيات', '🌟', '#1E90FF', 6, 1);

-- تحديث جدول reflections لإضافة علاقة مع جدول التصنيفات
-- (الحقل category موجود بالفعل كنص، سنحتفظ به للتوافق مع البيانات القديمة)
-- ونضيف حقل جديد للربط بجدول التصنيفات

ALTER TABLE reflections 
ADD COLUMN category_id INT DEFAULT NULL AFTER category,
ADD CONSTRAINT fk_reflection_category 
    FOREIGN KEY (category_id) REFERENCES reflection_categories(id) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE;

-- إضافة index للبحث السريع
ALTER TABLE reflections ADD INDEX idx_category_id (category_id);

-- تحديث البيانات الموجودة لربطها بالتصنيفات الجديدة
UPDATE reflections r
INNER JOIN reflection_categories rc ON r.category = rc.name_en
SET r.category_id = rc.id
WHERE r.category IS NOT NULL;

-- ملاحظة: سنحتفظ بحقل category القديم للتوافق مع الإصدارات السابقة
-- ولكن سنستخدم category_id في الإصدارات الجديدة

-- إنشاء view لتسهيل الاستعلامات
CREATE OR REPLACE VIEW v_reflections_with_categories AS
SELECT 
    r.*,
    rc.name_ar as category_name_ar,
    rc.name_en as category_name_en,
    rc.icon as category_icon,
    rc.color as category_color
FROM reflections r
LEFT JOIN reflection_categories rc ON r.category_id = rc.id;

-- إضافة تعليقات توضيحية
ALTER TABLE reflection_categories COMMENT = 'جدول تصنيفات التأملات الروحية - قابل للإدارة من لوحة التحكم';
ALTER TABLE reflections COMMENT = 'جدول التأملات الروحية';

-- عرض التصنيفات الحالية
SELECT * FROM reflection_categories ORDER BY display_order;

COMMIT;
