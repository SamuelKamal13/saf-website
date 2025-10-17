-- Attendance and Absence Tracking App Database Schema
-- Created: October 17, 2025
-- Database: if0_40147034_saf_app_db
-- Compatible with InfinityFree hosting (no Views, no Procedures, no Triggers)

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS if0_40147034_saf_app_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE if0_40147034_saf_app_db;

-- ========================================
-- Table: users
-- ========================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'servant', 'attendee') DEFAULT 'attendee',
    barcode_id VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_barcode (barcode_id),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: events
-- ========================================
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('mass', 'tasbeha', 'meeting', 'activity') NOT NULL,
    date DATETIME NOT NULL,
    barcode VARCHAR(255) NOT NULL,
    shared_barcode VARCHAR(100) NULL COMMENT 'Shared barcode for event type (e.g., MASS_BARCODE)',
    description TEXT,
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_type (type),
    INDEX idx_date (date),
    INDEX idx_barcode (barcode),
    INDEX idx_shared_barcode (shared_barcode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Barcode can be reused for multiple events to track attendance history';

-- ========================================
-- Table: attendance
-- ========================================
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    status ENUM('present', 'absent', 'excused') DEFAULT 'present',
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (user_id, event_id),
    INDEX idx_user (user_id),
    INDEX idx_event (event_id),
    INDEX idx_status (status),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: announcements
-- ========================================
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author VARCHAR(255),
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_pinned (is_pinned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: reflection_categories
-- ========================================
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

-- ========================================
-- Table: reflections
-- ========================================
CREATE TABLE IF NOT EXISTS reflections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author VARCHAR(255) NOT NULL,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    category VARCHAR(100),
    category_id INT DEFAULT NULL,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_author (author),
    INDEX idx_category (category),
    INDEX idx_category_id (category_id),
    FOREIGN KEY (category_id) REFERENCES reflection_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: event_type_barcodes
-- ========================================
CREATE TABLE IF NOT EXISTS event_type_barcodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type ENUM('mass', 'tasbeha', 'meeting', 'activity') NOT NULL UNIQUE,
    barcode VARCHAR(100) NOT NULL UNIQUE,
    arabic_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_barcode (barcode),
    INDEX idx_type (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: sessions (for authentication tokens)
-- ========================================
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: notifications
-- ========================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- Table: notification_logs
-- ========================================
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

-- ========================================
-- Table: pending_notifications
-- ========================================
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

-- ========================================
-- Insert default admin user
-- Password: admin123 (hashed with MD5 - should use bcrypt in production)
-- ========================================
INSERT INTO users (name, email, password, role, barcode_id, phone) VALUES
('Admin User', 'admin@church.com', MD5('admin123'), 'admin', 'ADMIN001', '1234567890');

-- ========================================
-- Insert sample events
-- ========================================
INSERT INTO events (name, type, date, barcode, description, location) VALUES
('Sunday Mass', 'mass', '2025-10-12 09:00:00', 'MASS_20251012_001', 'Regular Sunday Mass', 'Main Church'),
('Friday Tasbeha', 'tasbeha', '2025-10-10 19:00:00', 'TASBEHA_20251010_001', 'Evening Tasbeha Prayer', 'Main Church'),
('Youth Meeting', 'meeting', '2025-10-13 18:00:00', 'MEETING_20251013_001', 'Monthly youth gathering', 'Meeting Hall'),
('Community Activity', 'activity', '2025-10-15 15:00:00', 'ACTIVITY_20251015_001', 'Community service event', 'Community Center');

-- ========================================
-- Insert sample announcement
-- ========================================
INSERT INTO announcements (title, content, author, is_pinned) VALUES
('Welcome to Attendance Tracker', 'This app helps you track attendance for all church activities. Please scan the QR code at each event.', 'Admin', TRUE);

-- ========================================
-- Insert sample reflection
-- ========================================
INSERT INTO reflections (title, content, author, category) VALUES
('Daily Meditation on Faith', 'Faith is not just believing, but trusting in God\'s plan for us. Let us strengthen our faith through prayer and service.', 'Father John', 'Faith');

-- ========================================
-- Insert default shared barcodes for event types
-- ========================================
INSERT INTO event_type_barcodes (event_type, barcode, arabic_name, description, is_active) VALUES
('mass', 'MASS_SHARED', 'القداس الإلهي', 'باركود مشترك لجميع القداسات', 1),
('tasbeha', 'TASBEHA_SHARED', 'التسبحة', 'باركود مشترك لجميع التسابيح', 1),
('meeting', 'MEETING_SHARED', 'الاجتماع', 'باركود مشترك لجميع الاجتماعات', 1),
('activity', 'ACTIVITY_SHARED', 'النشاط', 'باركود مشترك لجميع الأنشطة', 1);

-- ========================================
-- Insert default reflection categories
-- ========================================
INSERT INTO reflection_categories (name_ar, name_en, description, icon, color, display_order, is_active) VALUES
('الصلاة', 'Prayer', 'تأملات وتعاليم عن الصلاة والحياة الصلائية', '🙏', '#8B0000', 1, 1),
('دراسة الكتاب المقدس', 'Bible Study', 'تأملات في نصوص الكتاب المقدس وشروحاته', '📖', '#D4AF37', 2, 1),
('القديسين', 'Saints', 'سير وتعاليم القديسين والآباء', '✨', '#800020', 3, 1),
('الروحانية', 'Spirituality', 'تأملات في الحياة الروحية والنمو الروحي', '💫', '#4B0082', 4, 1),
('الأسرة', 'Family', 'تأملات عن الأسرة المسيحية والعلاقات الأسرية', '👨‍👩‍👧‍👦', '#2E8B57', 5, 1),
('الشباب', 'Youth', 'تأملات موجهة للشباب والفتيات', '🌟', '#1E90FF', 6, 1);

-- ========================================
-- End of schema
-- ========================================