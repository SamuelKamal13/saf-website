<?php
// Simple test to debug the attendance.php logic

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$eventBarcode = 'MASS_SHARED';
$userId = 1;

// Define today's date range
$todayStart = date('Y-m-d 00:00:00');
$todayEnd = date('Y-m-d 23:59:59');

echo "Testing with:\n";
echo "Event Barcode: $eventBarcode\n";
echo "User ID: $userId\n";
echo "Today Start: $todayStart\n";
echo "Today End: $todayEnd\n\n";

// Check if this is a shared barcode
$sharedBarcodeQuery = "SELECT event_type, barcode, arabic_name FROM event_type_barcodes 
                      WHERE barcode = :barcode AND is_active = 1";
$sharedStmt = $db->prepare($sharedBarcodeQuery);
$sharedStmt->bindParam(':barcode', $eventBarcode);
$sharedStmt->execute();

$isSharedBarcode = false;

if ($sharedStmt->rowCount() > 0) {
    echo "✅ This IS a shared barcode\n";
    $isSharedBarcode = true;
    $sharedBarcodeData = $sharedStmt->fetch();
    echo "Type: {$sharedBarcodeData['event_type']}\n";
    echo "Name: {$sharedBarcodeData['arabic_name']}\n\n";
} else {
    echo "❌ This is NOT a shared barcode\n\n";
}

// Check attendance
if ($isSharedBarcode) {
    echo "Checking attendance with shared barcode...\n";
    $checkQuery = "SELECT a.id, a.timestamp FROM attendance a
                   JOIN events e ON a.event_id = e.id
                   WHERE a.user_id = :user_id 
                   AND e.shared_barcode = :shared_barcode
                   AND a.timestamp >= :today_start 
                   AND a.timestamp <= :today_end";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':user_id', $userId);
    $checkStmt->bindParam(':shared_barcode', $eventBarcode);
    $checkStmt->bindParam(':today_start', $todayStart);
    $checkStmt->bindParam(':today_end', $todayEnd);
    $checkStmt->execute();
    
    echo "Found records: " . $checkStmt->rowCount() . "\n";
    
    if ($checkStmt->rowCount() > 0) {
        echo "✅ Attendance already recorded today!\n";
        while ($row = $checkStmt->fetch()) {
            echo "  - ID: {$row['id']}, Time: {$row['timestamp']}\n";
        }
    } else {
        echo "❌ No attendance found - can record\n";
    }
}
?>
