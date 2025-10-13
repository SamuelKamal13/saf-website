<?php
/**
 * Delete Reflection API Endpoint
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

// Only admin and servant can delete reflections
if ($user['role'] != 'admin' && $user['role'] != 'servant') {
    jsonResponse(false, 'Insufficient permissions', null, 403);
}

// Get reflection ID from query parameter
$reflectionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$reflectionId) {
    jsonResponse(false, 'Reflection ID is required', null, 400);
}

try {
    // Check if reflection exists
    $checkQuery = "SELECT id, title FROM reflections WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $reflectionId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        jsonResponse(false, 'Reflection not found', null, 404);
    }
    
    $reflection = $checkStmt->fetch();
    
    // Delete reflection (hard delete)
    $query = "DELETE FROM reflections WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $reflectionId);
    $stmt->execute();
    
    jsonResponse(true, 'Reflection deleted successfully', [
        'reflection_id' => $reflectionId,
        'title' => $reflection['title']
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
