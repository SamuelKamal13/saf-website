<?php
/**
 * Delete User API Endpoint (Soft Delete)
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

// Only admin can delete users
if ($user['role'] != 'admin') {
    jsonResponse(false, 'Only administrators can delete users', null, 403);
}

// Get user ID from query parameter
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$userId) {
    jsonResponse(false, 'User ID is required', null, 400);
}

// Prevent deleting self
if ($userId == $user['id']) {
    jsonResponse(false, 'Cannot delete your own account', null, 400);
}

try {
    // Check if user exists
    $checkQuery = "SELECT id, name, email FROM users WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $userId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        jsonResponse(false, 'User not found', null, 404);
    }
    
    $targetUser = $checkStmt->fetch();
    
    // Soft delete user (set is_active = 0)
    $query = "UPDATE users SET is_active = 0 WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    
    jsonResponse(true, 'User deleted successfully', [
        'user_id' => $userId,
        'name' => $targetUser['name'],
        'email' => $targetUser['email']
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
