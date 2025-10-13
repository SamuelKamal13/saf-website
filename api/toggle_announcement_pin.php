<?php
/**
 * Toggle Announcement Pin Status API Endpoint
 * Method: POST
 * Parameters: id (required)
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

// Only admin and servant can toggle pin status
if ($user['role'] != 'admin' && $user['role'] != 'servant') {
    jsonResponse(false, 'Insufficient permissions', null, 403);
}

// Get announcement ID from query parameter
$announcementId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$announcementId) {
    jsonResponse(false, 'Announcement ID is required', null, 400);
}

try {
    // Check if announcement exists and get current pin status
    $checkQuery = "SELECT id, is_pinned FROM announcements WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $announcementId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        jsonResponse(false, 'Announcement not found', null, 404);
    }
    
    $announcement = $checkStmt->fetch();
    $newPinStatus = $announcement['is_pinned'] == 1 ? 0 : 1;
    
    // Toggle pin status
    $query = "UPDATE announcements SET is_pinned = :is_pinned WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':is_pinned', $newPinStatus);
    $stmt->bindParam(':id', $announcementId);
    $stmt->execute();
    
    jsonResponse(true, 'Pin status updated successfully', [
        'announcement_id' => $announcementId,
        'is_pinned' => $newPinStatus
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
