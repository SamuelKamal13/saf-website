-- إنشاء جدول الإشعارات
-- نظام إشعارات كامل بدون Firebase

-- جدول الإشعارات الرئيسي
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL COMMENT 'عنوان الإشعار',
    body TEXT NOT NULL COMMENT 'محتوى الإشعار',
    type ENUM('custom', 'reflection', 'announcement', 'event', 'system') DEFAULT 'custom' COMMENT 'نوع الإشعار',
    
    -- معلومات الجدولة
    send_immediately BOOLEAN DEFAULT TRUE COMMENT 'إرسال فوري أم مجدول',
    scheduled_at DATETIME NULL COMMENT 'وقت الإرسال المجدول',
    repeat_type ENUM('none', 'daily', 'weekly', 'monthly') DEFAULT 'none' COMMENT 'نوع التكرار',
    last_sent_at DATETIME NULL COMMENT 'آخر وقت تم فيه الإرسال',
    next_send_at DATETIME NULL COMMENT 'موعد الإرسال القادم للإشعارات المتكررة',
    
    -- حالة الإرسال
    is_sent BOOLEAN DEFAULT FALSE COMMENT 'هل تم إرساله',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'هل الإشعار نشط',
    
    -- المستهدفون
    target_users VARCHAR(20) DEFAULT 'all' COMMENT 'all, members, servants, admins, specific',
    specific_user_ids TEXT NULL COMMENT 'IDs المستخدمين المحددين (JSON array)',
    
    -- بيانات إضافية
    data JSON NULL COMMENT 'بيانات إضافية (link, image, action)',
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal' COMMENT 'أولوية الإشعار',
    
    -- معلومات الإنشاء
    created_by INT NULL COMMENT 'ID الأدمن الذي أنشأ الإشعار',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_scheduled_at (scheduled_at),
    INDEX idx_is_sent (is_sent),
    INDEX idx_next_send_at (next_send_at),
    INDEX idx_type (type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول سجل إرسال الإشعارات (لمعرفة من استلم ماذا)
CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    user_id INT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE COMMENT 'هل قرأ المستخدم الإشعار',
    read_at DATETIME NULL,
    
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_notification_id (notification_id),
    INDEX idx_is_read (is_read),
    UNIQUE KEY unique_notification_user (notification_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الإشعارات المعلقة (للمستخدمين المتصلين)
CREATE TABLE IF NOT EXISTS pending_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    UNIQUE KEY unique_pending (user_id, notification_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- عرض لإحصائيات الإشعارات
CREATE OR REPLACE VIEW notification_stats AS
SELECT 
    n.id,
    n.title,
    n.type,
    n.created_at,
    COUNT(DISTINCT nl.user_id) as total_sent,
    COUNT(DISTINCT CASE WHEN nl.is_read = TRUE THEN nl.user_id END) as total_read,
    ROUND((COUNT(DISTINCT CASE WHEN nl.is_read = TRUE THEN nl.user_id END) * 100.0) / 
          NULLIF(COUNT(DISTINCT nl.user_id), 0), 2) as read_percentage
FROM notifications n
LEFT JOIN notification_logs nl ON n.id = nl.notification_id
WHERE n.is_sent = TRUE
GROUP BY n.id, n.title, n.type, n.created_at;

-- إضافة بيانات تجريبية (اختياري)
-- INSERT INTO notifications (title, body, type, send_immediately, target_users, created_by)
-- VALUES 
-- ('مرحباً بك', 'أهلاً بك في نظام الإشعارات الجديد', 'system', TRUE, 'all', 1),
-- ('تذكير يومي', 'لا تنسى صلاتك اليومية', 'system', FALSE, 'all', 1);

COMMIT;
