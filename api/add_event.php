<?php
/**
 * Add Event API Endpoint
 * Method: POST
 * Parameters: name, type, date, barcode, description, location
 * Headers: Authorization: Bearer {token}
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod('POST');

$database = new Database();
$db = $database->getConnection();

// Validate token
$token = getBearerToken();
if (!$token) {
    jsonResponse(false, 'Authentication required', null, 401);
}

$user = validateToken($db, $token);
if (!$user) {
    jsonResponse(false, 'Invalid or expired token', null, 401);
}

// Only admin and servant can add events
if ($user['role'] != 'admin' && $user['role'] != 'servant') {
    jsonResponse(false, 'Insufficient permissions', null, 403);
}

$data = getJsonInput();

// Validate required fields
if (empty($data['name']) || empty($data['type']) || empty($data['date']) || empty($data['barcode'])) {
    jsonResponse(false, 'Name, type, date, and barcode are required', null, 400);
}

$name = sanitizeInput($data['name']);
$type = sanitizeInput($data['type']);
$date = sanitizeInput($data['date']);
$barcode = sanitizeInput($data['barcode']);
$description = isset($data['description']) ? sanitizeInput($data['description']) : null;
$location = isset($data['location']) ? sanitizeInput($data['location']) : null;

// Validate event type
$validTypes = ['mass', 'tasbeha', 'meeting', 'activity'];
if (!in_array($type, $validTypes)) {
    jsonResponse(false, 'Invalid event type. Must be: mass, tasbeha, meeting, or activity', null, 400);
}

try {
    // Get the shared barcode for this event type
    $sharedBarcodeQuery = "SELECT barcode FROM event_type_barcodes WHERE event_type = :event_type AND is_active = 1";
    $sharedStmt = $db->prepare($sharedBarcodeQuery);
    $sharedStmt->bindParam(':event_type', $type);
    $sharedStmt->execute();
    
    $sharedBarcode = null;
    if ($sharedStmt->rowCount() > 0) {
        $sharedData = $sharedStmt->fetch();
        $sharedBarcode = $sharedData['barcode'];
    }
    
    // Note: Barcode can be used multiple times (no uniqueness check)
    // This allows users to scan the same barcode multiple times
    // Attendance history is tracked in the attendance table
    
    // Insert event with shared_barcode
    $query = "INSERT INTO events (name, type, date, barcode, shared_barcode, description, location, is_active) 
              VALUES (:name, :type, :date, :barcode, :shared_barcode, :description, :location, 1)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':barcode', $barcode);
    $stmt->bindParam(':shared_barcode', $sharedBarcode);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':location', $location);
    $stmt->execute();
    
    $eventId = $db->lastInsertId();
    
    // Get the created event
    $getQuery = "SELECT * FROM events WHERE id = :id";
    $getStmt = $db->prepare($getQuery);
    $getStmt->bindParam(':id', $eventId);
    $getStmt->execute();
    $event = $getStmt->fetch();
    
    jsonResponse(true, 'Event created successfully', [
        'event' => $event
    ], 201);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
