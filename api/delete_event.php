<?php
/**
 * Delete Event API Endpoint
 * Method: DELETE
 * Parameters: id (required)
 * Headers: Authorization: Bearer {token}
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod('DELETE');

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

// Only admin and servant can delete events
if ($user['role'] != 'admin' && $user['role'] != 'servant') {
    jsonResponse(false, 'Insufficient permissions', null, 403);
}

// Get event ID from query parameter
$eventId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$eventId) {
    jsonResponse(false, 'Event ID is required', null, 400);
}

try {
    // Check if event exists
    $checkQuery = "SELECT id, name FROM events WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $eventId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        jsonResponse(false, 'Event not found', null, 404);
    }
    
    $event = $checkStmt->fetch();
    
    // Soft delete: Set is_active to 0 instead of actually deleting
    $query = "UPDATE events SET is_active = 0 WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $eventId);
    $stmt->execute();
    
    jsonResponse(true, 'Event deleted successfully', [
        'event_id' => $eventId,
        'event_name' => $event['name']
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
