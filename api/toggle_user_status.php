<?php
/**
 * Toggle User Status API Endpoint
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

// Only admin can toggle user status
if ($user['role'] != 'admin') {
    jsonResponse(false, 'Only administrators can toggle user status', null, 403);
}

// Get user ID from query parameter
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$userId) {
    jsonResponse(false, 'User ID is required', null, 400);
}

// Prevent toggling self
if ($userId == $user['id']) {
    jsonResponse(false, 'Cannot toggle your own account status', null, 400);
}

try {
    // Check if user exists and get current status
    $checkQuery = "SELECT id, name, is_active FROM users WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $userId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        jsonResponse(false, 'User not found', null, 404);
    }
    
    $targetUser = $checkStmt->fetch();
    $newStatus = $targetUser['is_active'] == 1 ? 0 : 1;
    
    // Toggle status
    $query = "UPDATE users SET is_active = :is_active WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':is_active', $newStatus);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    
    jsonResponse(true, 'User status updated successfully', [
        'user_id' => $userId,
        'name' => $targetUser['name'],
        'is_active' => $newStatus
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
