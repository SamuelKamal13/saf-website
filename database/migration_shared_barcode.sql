-- ========================================
-- Migration: Add Shared Barcode System
-- Date: October 6, 2025
-- Purpose: Enable shared barcodes by event type for recurring events
-- ========================================

USE attendance_app;

-- ========================================
-- Step 1: Add shared_barcode column to events table
-- This will hold the permanent barcode for event types
-- ========================================
ALTER TABLE events 
ADD COLUMN shared_barcode VARCHAR(100) NULL COMMENT 'Shared barcode for event type (e.g., MASS_BARCODE)' AFTER barcode,
ADD INDEX idx_shared_barcode (shared_barcode);

-- ========================================
-- Step 2: Create event_type_barcodes table
-- This table stores the default/shared barcode for each event type
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
-- Step 3: Insert default shared barcodes for event types
-- ========================================
INSERT INTO event_type_barcodes (event_type, barcode, arabic_name, description, is_active) VALUES
('mass', 'MASS_SHARED', 'القداس الإلهي', 'باركود مشترك لجميع القداسات', 1),
('tasbeha', 'TASBEHA_SHARED', 'التسبحة', 'باركود مشترك لجميع التسابيح', 1),
('meeting', 'MEETING_SHARED', 'الاجتماع', 'باركود مشترك لجميع الاجتماعات', 1),
('activity', 'ACTIVITY_SHARED', 'النشاط', 'باركود مشترك لجميع الأنشطة', 1);

-- ========================================
-- Step 4: Update existing events with shared barcodes
-- ========================================
UPDATE events e
JOIN event_type_barcodes etb ON e.type = etb.event_type
SET e.shared_barcode = etb.barcode
WHERE e.is_active = 1;

-- ========================================
-- Step 5: Create view for active shared barcodes
-- ========================================
CREATE OR REPLACE VIEW v_active_event_barcodes AS
SELECT 
    etb.id,
    etb.event_type,
    etb.barcode,
    etb.arabic_name,
    etb.description,
    COUNT(e.id) AS total_events,
    COUNT(DISTINCT a.user_id) AS total_unique_attendees,
    COUNT(a.id) AS total_attendance_records
FROM event_type_barcodes etb
LEFT JOIN events e ON e.shared_barcode = etb.barcode
LEFT JOIN attendance a ON e.id = a.event_id
WHERE etb.is_active = 1
GROUP BY etb.id, etb.event_type, etb.barcode, etb.arabic_name, etb.description;

-- ========================================
-- Step 6: Update attendance tracking logic
-- The attendance.php API will now:
-- 1. Accept a shared_barcode scan
-- 2. Find the most recent event with that shared_barcode
-- 3. OR create a new event instance automatically
-- 4. Record attendance with current timestamp
-- ========================================

-- Create a stored procedure to handle attendance with shared barcode
DELIMITER //

DROP PROCEDURE IF EXISTS record_attendance_shared_barcode//

CREATE PROCEDURE record_attendance_shared_barcode(
    IN p_user_id INT,
    IN p_shared_barcode VARCHAR(100),
    OUT p_event_id INT,
    OUT p_attendance_id INT,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_event_type VARCHAR(50);
    DECLARE v_event_name VARCHAR(255);
    DECLARE v_today_start DATETIME;
    DECLARE v_today_end DATETIME;
    DECLARE v_existing_attendance INT;
    
    -- Set today's date range
    SET v_today_start = DATE(NOW());
    SET v_today_end = DATE_ADD(v_today_start, INTERVAL 1 DAY);
    
    -- Get event type from shared barcode
    SELECT event_type, arabic_name INTO v_event_type, v_event_name
    FROM event_type_barcodes
    WHERE barcode = p_shared_barcode AND is_active = 1
    LIMIT 1;
    
    -- Check if event type exists
    IF v_event_type IS NULL THEN
        SET p_message = 'Invalid shared barcode';
        SET p_event_id = NULL;
        SET p_attendance_id = NULL;
    ELSE
        -- Try to find today's event with this shared barcode
        SELECT id INTO p_event_id
        FROM events
        WHERE shared_barcode = p_shared_barcode
        AND date >= v_today_start
        AND date < v_today_end
        AND is_active = 1
        ORDER BY date DESC
        LIMIT 1;
        
        -- If no event found for today, create one
        IF p_event_id IS NULL THEN
            INSERT INTO events (name, type, date, barcode, shared_barcode, description, is_active)
            VALUES (
                CONCAT(v_event_name, ' - ', DATE_FORMAT(NOW(), '%Y-%m-%d')),
                v_event_type,
                NOW(),
                CONCAT(p_shared_barcode, '_', DATE_FORMAT(NOW(), '%Y%m%d_%H%i%s')),
                p_shared_barcode,
                CONCAT('تم إنشاؤها تلقائياً عند المسح - ', NOW()),
                1
            );
            
            SET p_event_id = LAST_INSERT_ID();
        END IF;
        
        -- Check if attendance already recorded for this user today
        SELECT id INTO v_existing_attendance
        FROM attendance
        WHERE user_id = p_user_id
        AND event_id = p_event_id
        LIMIT 1;
        
        IF v_existing_attendance IS NOT NULL THEN
            SET p_message = 'Attendance already recorded for today';
            SET p_attendance_id = v_existing_attendance;
        ELSE
            -- Record attendance
            INSERT INTO attendance (user_id, event_id, status, timestamp)
            VALUES (p_user_id, p_event_id, 'present', NOW());
            
            SET p_attendance_id = LAST_INSERT_ID();
            SET p_message = 'Attendance recorded successfully';
        END IF;
    END IF;
END//

DELIMITER ;

-- ========================================
-- Verification Queries
-- ========================================

-- Show all shared barcodes
SELECT * FROM event_type_barcodes;

-- Show events with shared barcodes
SELECT 
    e.id,
    e.name,
    e.type,
    e.date,
    e.barcode AS unique_barcode,
    e.shared_barcode,
    etb.arabic_name AS type_name
FROM events e
LEFT JOIN event_type_barcodes etb ON e.shared_barcode = etb.barcode
ORDER BY e.date DESC;

-- Show shared barcode statistics
SELECT * FROM v_active_event_barcodes;

-- ========================================
-- End of Migration
-- ========================================

