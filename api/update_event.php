<?php
/**
 * Update Event API Endpoint
 * Method: PUT or POST
 * Parameters: id (in query), name, type, date, barcode, description, location
 * Headers: Authorization: Bearer {token}
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Allow both PUT and POST
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method. Expected PUT or POST', null, 405);
}

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

// Only admin and servant can update events
if ($user['role'] != 'admin' && $user['role'] != 'servant') {
    jsonResponse(false, 'Insufficient permissions', null, 403);
}

// Get event ID from query parameter
$eventId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$eventId) {
    jsonResponse(false, 'Event ID is required', null, 400);
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
    // Check if event exists
    $checkQuery = "SELECT id FROM events WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $eventId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        jsonResponse(false, 'Event not found', null, 404);
    }
    
    // Note: Barcode uniqueness check removed
    // Multiple events can share the same barcode for repeated activities
    // Attendance is tracked with timestamp in attendance table
    
    // Update event
    $query = "UPDATE events 
              SET name = :name, 
                  type = :type, 
                  date = :date, 
                  barcode = :barcode, 
                  description = :description, 
                  location = :location 
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':date', $date);
    $stmt->bindParam(':barcode', $barcode);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':id', $eventId);
    $stmt->execute();
    
    // Get updated event
    $getQuery = "SELECT * FROM events WHERE id = :id";
    $getStmt = $db->prepare($getQuery);
    $getStmt->bindParam(':id', $eventId);
    $getStmt->execute();
    $event = $getStmt->fetch();
    
    jsonResponse(true, 'Event updated successfully', [
        'event' => $event
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
