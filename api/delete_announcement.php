<?php
/**
 * Delete Announcement API Endpoint
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

// Only admin and servant can delete announcements
if ($user['role'] != 'admin' && $user['role'] != 'servant') {
    jsonResponse(false, 'Insufficient permissions', null, 403);
}

// Get announcement ID from query parameter
$announcementId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$announcementId) {
    jsonResponse(false, 'Announcement ID is required', null, 400);
}

try {
    // Check if announcement exists
    $checkQuery = "SELECT id, title FROM announcements WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $announcementId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        jsonResponse(false, 'Announcement not found', null, 404);
    }
    
    $announcement = $checkStmt->fetch();
    
    // Delete announcement (actual delete, not soft delete)
    $query = "DELETE FROM announcements WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $announcementId);
    $stmt->execute();
    
    jsonResponse(true, 'Announcement deleted successfully', [
        'announcement_id' => $announcementId,
        'title' => $announcement['title']
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
