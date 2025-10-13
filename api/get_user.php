<?php
/**
 * Get User Data API Endpoint
 * Method: GET
 * Parameters: user_id (optional, defaults to authenticated user)
 * Headers: Authorization: Bearer {token}
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod('GET');

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

// Get all users if no specific user_id is provided (admin/servant view)
$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

// Check permissions
if ($userId && $user['role'] != 'admin' && $user['role'] != 'servant' && $userId != $user['id']) {
    jsonResponse(false, 'Insufficient permissions', null, 403);
}

try {
    // If no user_id specified, return all users (admin/servant view)
    if (!$userId) {
        // Only admin and servant can view all users
        if ($user['role'] != 'admin' && $user['role'] != 'servant') {
            jsonResponse(false, 'Insufficient permissions to view all users', null, 403);
        }
        
        $query = "SELECT id, name, email, role, barcode_id, phone, created_at, is_active FROM users ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        jsonResponse(true, 'Users retrieved', [
            'users' => $users,
            'count' => count($users)
        ]);
    }
    
    // Single user query
    $query = "SELECT id, name, email, role, barcode_id, phone, created_at, is_active FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        jsonResponse(false, 'User not found', null, 404);
    }
    
    $userData = $stmt->fetch();
    
    // Get attendance statistics
    $statsQuery = "SELECT 
                   COUNT(*) as total_events,
                   SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                   SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                   SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count
                   FROM attendance 
                   WHERE user_id = :user_id";
    
    $statsStmt = $db->prepare($statsQuery);
    $statsStmt->bindParam(':user_id', $userId);
    $statsStmt->execute();
    $stats = $statsStmt->fetch();
    
    jsonResponse(true, 'User data retrieved', [
        'user' => $userData,
        'statistics' => $stats
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
