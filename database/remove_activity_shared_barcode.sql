-- ========================================
-- Remove Activity from Shared Barcodes
-- Date: October 7, 2025
-- Purpose: Delete 'activity' type from event_type_barcodes table
--          while keeping activity events in the events table
-- ========================================

USE attendance_app;

-- ========================================
-- Step 1: Remove shared_barcode from existing activity events
-- This will convert them to use only their unique barcodes
-- ========================================
UPDATE events
SET shared_barcode = NULL
WHERE type = 'activity' AND shared_barcode = 'ACTIVITY_SHARED';

-- ========================================
-- Step 2: Delete 'activity' from event_type_barcodes table
-- This removes the shared barcode for activities
-- ========================================
DELETE FROM event_type_barcodes
WHERE event_type = 'activity' AND barcode = 'ACTIVITY_SHARED';

-- ========================================
-- Verification Queries
-- ========================================

-- Show remaining shared barcodes (should not include activity)
SELECT 
    id,
    event_type,
    barcode,
    arabic_name,
    description,
    is_active
FROM event_type_barcodes
ORDER BY id;

-- Show activity events (should still exist with only unique barcodes)
SELECT 
    id,
    name,
    type,
    date,
    barcode AS unique_barcode,
    shared_barcode,
    is_active
FROM events
WHERE type = 'activity'
ORDER BY date DESC
LIMIT 10;

-- Count of events by type
SELECT 
    type,
    COUNT(*) AS count,
    SUM(CASE WHEN shared_barcode IS NOT NULL THEN 1 ELSE 0 END) AS with_shared_barcode,
    SUM(CASE WHEN shared_barcode IS NULL THEN 1 ELSE 0 END) AS without_shared_barcode
FROM events
WHERE is_active = 1
GROUP BY type;

-- ========================================
-- Expected Results:
-- ========================================
-- 1. event_type_barcodes should have only 3 rows: mass, tasbeha, meeting
-- 2. Activity events should still exist in events table
-- 3. Activity events should have shared_barcode = NULL
-- 4. Activity events will use only their unique barcodes
-- ========================================

-- End of script
