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
-- Note: Views are not supported on InfinityFree hosting
-- This functionality will be implemented in PHP API endpoints instead

-- ========================================
-- Step 6: Update attendance tracking logic
-- The attendance.php API will now:
-- 1. Accept a shared_barcode scan
-- 2. Find the most recent event with that shared_barcode
-- 3. OR create a new event instance automatically
-- 4. Record attendance with current timestamp
-- ========================================

-- Note: Stored procedures are not supported on InfinityFree hosting
-- The procedure logic has been moved to PHP in attendance.php

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

