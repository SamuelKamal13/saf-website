-- ========================================
-- Quick Setup: Reflection Categories
-- تنصيب سريع لجدول تصنيفات التأملات
-- ========================================

-- التحقق من وجود الجدول وإنشائه إذا لم يكن موجوداً
CREATE TABLE IF NOT EXISTS reflection_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_ar VARCHAR(100) NOT NULL UNIQUE,
    name_en VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT '📖',
    color VARCHAR(7) DEFAULT '#8B0000',
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة التصنيفات الافتراضية (فقط إذا لم تكن موجودة)
INSERT IGNORE INTO reflection_categories (name_ar, name_en, description, icon, color, display_order, is_active) VALUES
('الصلاة', 'Prayer', 'تأملات وتعاليم عن الصلاة والحياة الصلائية', '🙏', '#8B0000', 1, 1),
('دراسة الكتاب المقدس', 'Bible Study', 'تأملات في نصوص الكتاب المقدس وشروحاته', '📖', '#D4AF37', 2, 1),
('القديسين', 'Saints', 'سير وتعاليم القديسين والآباء', '✨', '#800020', 3, 1),
('الروحانية', 'Spirituality', 'تأملات في الحياة الروحية والنمو الروحي', '💫', '#4B0082', 4, 1),
('الأسرة', 'Family', 'تأملات عن الأسرة المسيحية والعلاقات الأسرية', '👨‍👩‍👧‍👦', '#2E8B57', 5, 1),
('الشباب', 'Youth', 'تأملات موجهة للشباب والفتيات', '🌟', '#1E90FF', 6, 1);

-- التحقق من وجود category_id في جدول reflections
-- إذا لم يكن موجوداً، أضفه
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'reflections'
    AND COLUMN_NAME = 'category_id'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE reflections ADD COLUMN category_id INT DEFAULT NULL AFTER category',
    'SELECT "category_id column already exists" AS status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- إضافة foreign key إذا لم يكن موجوداً
SET @fk_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'reflections'
    AND CONSTRAINT_NAME = 'fk_reflection_category'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE reflections ADD CONSTRAINT fk_reflection_category 
     FOREIGN KEY (category_id) REFERENCES reflection_categories(id) 
     ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "Foreign key already exists" AS status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- إضافة index إذا لم يكن موجوداً
SET @idx_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'reflections'
    AND INDEX_NAME = 'idx_category_id'
);

SET @sql = IF(@idx_exists = 0,
    'ALTER TABLE reflections ADD INDEX idx_category_id (category_id)',
    'SELECT "Index already exists" AS status'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- تحديث البيانات الموجودة
UPDATE reflections r
INNER JOIN reflection_categories rc ON r.category = rc.name_en
SET r.category_id = rc.id
WHERE r.category IS NOT NULL AND r.category_id IS NULL;

-- عرض النتيجة
SELECT 'Setup completed successfully!' AS status;
SELECT * FROM reflection_categories ORDER BY display_order;
