-- Attendance and Absence Tracking App Database Schema
-- Created: October 6, 2025
-- Database: attendance_app

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS attendance_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE attendance_app;

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
    description TEXT,
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_type (type),
    INDEX idx_date (date),
    INDEX idx_barcode (barcode)
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
-- Table: reflections
-- ========================================
CREATE TABLE IF NOT EXISTS reflections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author VARCHAR(255) NOT NULL,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    category VARCHAR(100),
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_author (author),
    INDEX idx_category (category)
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
-- Views for reporting
-- ========================================

-- View: User attendance statistics
CREATE OR REPLACE VIEW v_user_attendance_stats AS
SELECT 
    u.id AS user_id,
    u.name AS user_name,
    u.email,
    u.role,
    COUNT(a.id) AS total_events,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
    SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) AS excused_count,
    ROUND((SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 2) AS attendance_percentage
FROM users u
LEFT JOIN attendance a ON u.id = a.user_id
WHERE u.is_active = TRUE
GROUP BY u.id, u.name, u.email, u.role;

-- View: Event attendance summary
CREATE OR REPLACE VIEW v_event_attendance_summary AS
SELECT 
    e.id AS event_id,
    e.name AS event_name,
    e.type AS event_type,
    e.date AS event_date,
    e.location,
    COUNT(a.id) AS total_attendees,
    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
    SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) AS excused_count
FROM events e
LEFT JOIN attendance a ON e.id = a.event_id
WHERE e.is_active = TRUE
GROUP BY e.id, e.name, e.type, e.date, e.location;

-- ========================================
-- End of schema
-- ========================================
