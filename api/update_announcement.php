<?php
/**
 * Update Announcement API Endpoint
 * Method: PUT or POST
 * Parameters: id (in query), title, content, is_pinned
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

// Only admin and servant can update announcements
if ($user['role'] != 'admin' && $user['role'] != 'servant') {
    jsonResponse(false, 'Insufficient permissions', null, 403);
}

// Get announcement ID from query parameter
$announcementId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$announcementId) {
    jsonResponse(false, 'Announcement ID is required', null, 400);
}

$data = getJsonInput();

// Validate required fields
if (empty($data['title']) || empty($data['content'])) {
    jsonResponse(false, 'Title and content are required', null, 400);
}

$title = sanitizeInput($data['title']);
$content = sanitizeInput($data['content']);
$isPinned = isset($data['is_pinned']) ? intval($data['is_pinned']) : 0;

try {
    // Check if announcement exists
    $checkQuery = "SELECT id FROM announcements WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $announcementId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        jsonResponse(false, 'Announcement not found', null, 404);
    }
    
    // Update announcement
    $query = "UPDATE announcements 
              SET title = :title, 
                  content = :content, 
                  is_pinned = :is_pinned 
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':is_pinned', $isPinned);
    $stmt->bindParam(':id', $announcementId);
    $stmt->execute();
    
    // Get updated announcement
    $getQuery = "SELECT * FROM announcements WHERE id = :id";
    $getStmt = $db->prepare($getQuery);
    $getStmt->bindParam(':id', $announcementId);
    $getStmt->execute();
    $announcement = $getStmt->fetch();
    
    jsonResponse(true, 'Announcement updated successfully', [
        'announcement' => $announcement
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
