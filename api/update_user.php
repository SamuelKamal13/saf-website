<?php
/**
 * Update User API Endpoint
 * Method: PUT/POST
 * Parameters: id (query), name, email, phone, role, is_active (JSON body)
 * Headers: Authorization: Bearer {token}
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod(['PUT', 'POST']);

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

// Only admin can update users
if ($user['role'] != 'admin') {
    jsonResponse(false, 'Only administrators can update users', null, 403);
}

// Get user ID from query parameter
$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$userId) {
    jsonResponse(false, 'User ID is required', null, 400);
}

// Get JSON input
$data = getJsonInput();

// Validate required fields
if (!isset($data['name']) || !isset($data['email']) || !isset($data['role'])) {
    jsonResponse(false, 'Name, email, and role are required', null, 400);
}

$name = sanitizeInput($data['name']);
$email = sanitizeInput($data['email']);
$phone = isset($data['phone']) ? sanitizeInput($data['phone']) : null;
$role = sanitizeInput($data['role']);
$isActive = isset($data['is_active']) ? intval($data['is_active']) : 1;

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email format', null, 400);
}

// Validate role
$allowedRoles = ['admin', 'servant', 'member'];
if (!in_array($role, $allowedRoles)) {
    jsonResponse(false, 'Invalid role. Allowed: ' . implode(', ', $allowedRoles), null, 400);
}

// Validate phone format if provided
if ($phone && !preg_match('/^01[0-9]{9}$/', $phone)) {
    jsonResponse(false, 'Invalid phone format. Must be 01xxxxxxxxx', null, 400);
}

try {
    // Check if user exists
    $checkQuery = "SELECT id FROM users WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $userId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        jsonResponse(false, 'User not found', null, 404);
    }
    
    // Check email uniqueness (excluding current user)
    $emailCheckQuery = "SELECT id FROM users WHERE email = :email AND id != :id";
    $emailCheckStmt = $db->prepare($emailCheckQuery);
    $emailCheckStmt->bindParam(':email', $email);
    $emailCheckStmt->bindParam(':id', $userId);
    $emailCheckStmt->execute();
    
    if ($emailCheckStmt->rowCount() > 0) {
        jsonResponse(false, 'Email already exists', null, 400);
    }
    
    // Update user
    $query = "UPDATE users 
              SET name = :name, 
                  email = :email, 
                  phone = :phone, 
                  role = :role, 
                  is_active = :is_active 
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':is_active', $isActive);
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    
    // Get updated user
    $selectQuery = "SELECT id, name, email, phone, role, barcode_id, is_active, created_at 
                    FROM users WHERE id = :id";
    $selectStmt = $db->prepare($selectQuery);
    $selectStmt->bindParam(':id', $userId);
    $selectStmt->execute();
    $updatedUser = $selectStmt->fetch();
    
    jsonResponse(true, 'User updated successfully', ['user' => $updatedUser]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
