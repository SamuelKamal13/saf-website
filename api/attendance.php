<?php
/**
 * Record Attendance API Endpoint
 * Method: POST
 * Parameters: event_barcode, user_barcode_id OR token (authenticated)
 * Headers: Authorization: Bearer {token} (optional)
 * 
 * Supports both individual event barcodes AND shared event type barcodes
 * When scanning a shared barcode (e.g., MASS_SHARED):
 * - Finds today's event with that shared barcode
 * - If no event exists for today, creates a new event automatically
 * - Records attendance with current timestamp
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod('POST');

$database = new Database();
$db = $database->getConnection();

$data = getJsonInput();

// Validate input
if (empty($data['event_barcode'])) {
    jsonResponse(false, 'Event barcode is required', null, 400);
}

$eventBarcode = sanitizeInput($data['event_barcode']);
$userId = null;

// Check if authenticated via token
$token = getBearerToken();
if ($token) {
    $user = validateToken($db, $token);
    if ($user) {
        $userId = $user['id'];
    }
}

// If not authenticated by token, check for user_barcode_id
if (!$userId && !empty($data['user_barcode_id'])) {
    $userBarcodeId = sanitizeInput($data['user_barcode_id']);
    
    // Get user by barcode
    $userQuery = "SELECT id, name FROM users WHERE barcode_id = :barcode_id AND is_active = 1";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(':barcode_id', $userBarcodeId);
    $userStmt->execute();
    
    if ($userStmt->rowCount() > 0) {
        $userData = $userStmt->fetch();
        $userId = $userData['id'];
    }
}

// If still no user ID, return error
if (!$userId) {
    jsonResponse(false, 'User authentication required', null, 401);
}

try {
    $eventId = null;
    $isSharedBarcode = false;
    
    // Define today's date range (used for both finding events and checking attendance)
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd = date('Y-m-d 23:59:59');
    
    // First, check if this is a shared barcode (event type barcode)
    $sharedBarcodeQuery = "SELECT event_type, barcode, arabic_name FROM event_type_barcodes 
                          WHERE barcode = :barcode AND is_active = 1";
    $sharedStmt = $db->prepare($sharedBarcodeQuery);
    $sharedStmt->bindParam(':barcode', $eventBarcode);
    $sharedStmt->execute();
    
    if ($sharedStmt->rowCount() > 0) {
        // This is a shared barcode
        $isSharedBarcode = true;
        $sharedBarcodeData = $sharedStmt->fetch();
        $eventType = $sharedBarcodeData['event_type'];
        $arabicName = $sharedBarcodeData['arabic_name'];
        
        // Find today's event with this shared barcode
        
        $todayEventQuery = "SELECT id FROM events 
                           WHERE shared_barcode = :shared_barcode 
                           AND date >= :today_start 
                           AND date <= :today_end 
                           AND is_active = 1 
                           ORDER BY date DESC 
                           LIMIT 1";
        $todayStmt = $db->prepare($todayEventQuery);
        $todayStmt->bindParam(':shared_barcode', $eventBarcode);
        $todayStmt->bindParam(':today_start', $todayStart);
        $todayStmt->bindParam(':today_end', $todayEnd);
        $todayStmt->execute();
        
        if ($todayStmt->rowCount() > 0) {
            // Event exists for today
            $todayEvent = $todayStmt->fetch();
            $eventId = $todayEvent['id'];
        } else {
            // Create new event for today automatically
            $currentDateTime = date('Y-m-d H:i:s');
            $uniqueBarcode = $eventBarcode . '_' . date('Ymd_His');
            $eventName = $arabicName . ' - ' . date('Y-m-d');
            $description = 'تم إنشاؤها تلقائياً عند المسح - ' . date('Y-m-d H:i:s');
            
            $createEventQuery = "INSERT INTO events (name, type, date, barcode, shared_barcode, description, is_active) 
                                VALUES (:name, :type, :date, :barcode, :shared_barcode, :description, 1)";
            $createStmt = $db->prepare($createEventQuery);
            $createStmt->bindParam(':name', $eventName);
            $createStmt->bindParam(':type', $eventType);
            $createStmt->bindParam(':date', $currentDateTime);
            $createStmt->bindParam(':barcode', $uniqueBarcode);
            $createStmt->bindParam(':shared_barcode', $eventBarcode);
            $createStmt->bindParam(':description', $description);
            $createStmt->execute();
            
            $eventId = $db->lastInsertId();
        }
    } else {
        // Not a shared barcode, check for individual event barcode
        $eventQuery = "SELECT id FROM events WHERE (barcode = :barcode OR shared_barcode = :barcode) AND is_active = 1";
        $eventStmt = $db->prepare($eventQuery);
        $eventStmt->bindParam(':barcode', $eventBarcode);
        $eventStmt->execute();
        
        if ($eventStmt->rowCount() == 0) {
            jsonResponse(false, 'Invalid event barcode', null, 404);
        }
        
        $event = $eventStmt->fetch();
        $eventId = $event['id'];
    }
    
    // Check if attendance already recorded for this user with this shared barcode TODAY
    // For shared barcodes, check all events with same shared barcode
    // For individual barcodes, check only this specific event
    if ($isSharedBarcode) {
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
    } else {
        $checkQuery = "SELECT id, timestamp FROM attendance 
                       WHERE user_id = :user_id 
                       AND event_id = :event_id 
                       AND timestamp >= :today_start 
                       AND timestamp <= :today_end";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':user_id', $userId);
        $checkStmt->bindParam(':event_id', $eventId);
        $checkStmt->bindParam(':today_start', $todayStart);
        $checkStmt->bindParam(':today_end', $todayEnd);
    }
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        $existingAttendance = $checkStmt->fetch();
        
        // Get event details for response
        $eventDetailsQuery = "SELECT e.*, u.name as user_name 
                             FROM events e, users u 
                             WHERE e.id = :event_id AND u.id = :user_id";
        $detailsStmt = $db->prepare($eventDetailsQuery);
        $detailsStmt->bindParam(':event_id', $eventId);
        $detailsStmt->bindParam(':user_id', $userId);
        $detailsStmt->execute();
        $eventDetails = $detailsStmt->fetch();
        
        jsonResponse(false, 'تم تسجيل حضورك بالفعل لهذه الفعالية اليوم', [
            'attendance' => $existingAttendance,
            'event' => $eventDetails,
            'message_ar' => 'حضورك مسجل من قبل اليوم في ' . date('H:i', strtotime($existingAttendance['timestamp']))
        ], 409);
    }
    
    // Record attendance with current timestamp
    $insertQuery = "INSERT INTO attendance (user_id, event_id, status, timestamp) 
                   VALUES (:user_id, :event_id, 'present', NOW())";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':user_id', $userId);
    $insertStmt->bindParam(':event_id', $eventId);
    
    if ($insertStmt->execute()) {
        $attendanceId = $db->lastInsertId();
        
        // Get attendance details
        $getQuery = "SELECT a.*, u.name as user_name, e.name as event_name, e.type as event_type, 
                     e.shared_barcode, etb.arabic_name as type_arabic_name
                     FROM attendance a 
                     JOIN users u ON a.user_id = u.id 
                     JOIN events e ON a.event_id = e.id 
                     LEFT JOIN event_type_barcodes etb ON e.shared_barcode = etb.barcode
                     WHERE a.id = :id";
        $getStmt = $db->prepare($getQuery);
        $getStmt->bindParam(':id', $attendanceId);
        $getStmt->execute();
        $attendance = $getStmt->fetch();
        
        jsonResponse(true, 'تم تسجيل حضورك بنجاح', [
            'attendance' => $attendance,
            'is_shared_barcode' => $isSharedBarcode,
            'scan_time' => date('Y-m-d H:i:s'),
            'message_ar' => 'تم تسجيل حضورك في ' . ($attendance['type_arabic_name'] ?? $attendance['event_name'])
        ], 201);
    } else {
        jsonResponse(false, 'Failed to record attendance', null, 500);
    }
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
