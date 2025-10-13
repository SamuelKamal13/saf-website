<?php
/**
 * Update Reflection API Endpoint
 * Method: PUT/POST
 * Parameters: id (query), title, content, category, image_url (JSON body)
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

// Only admin and servant can update reflections
if ($user['role'] != 'admin' && $user['role'] != 'servant') {
    jsonResponse(false, 'Insufficient permissions', null, 403);
}

// Get reflection ID from query parameter
$reflectionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$reflectionId) {
    jsonResponse(false, 'Reflection ID is required', null, 400);
}

// Get JSON input
$data = getJsonInput();

// Validate required fields
if (!isset($data['title']) || !isset($data['content']) || !isset($data['category'])) {
    jsonResponse(false, 'Title, content, and category are required', null, 400);
}

$title = sanitizeInput($data['title']);
$content = sanitizeInput($data['content']);
$category = sanitizeInput($data['category']);
$imageUrl = isset($data['image_url']) ? sanitizeInput($data['image_url']) : null;

// Validate category
$allowedCategories = ['Prayer', 'Bible Study', 'Saints', 'Spirituality', 'Family', 'Youth'];
if (!in_array($category, $allowedCategories)) {
    jsonResponse(false, 'Invalid category. Allowed: ' . implode(', ', $allowedCategories), null, 400);
}

try {
    // Check if reflection exists
    $checkQuery = "SELECT id FROM reflections WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $reflectionId);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        jsonResponse(false, 'Reflection not found', null, 404);
    }
    
    // Update reflection
    $query = "UPDATE reflections 
              SET title = :title, 
                  content = :content, 
                  category = :category, 
                  image_url = :image_url 
              WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':image_url', $imageUrl);
    $stmt->bindParam(':id', $reflectionId);
    $stmt->execute();
    
    // Get updated reflection
    $selectQuery = "SELECT * FROM reflections WHERE id = :id";
    $selectStmt = $db->prepare($selectQuery);
    $selectStmt->bindParam(':id', $reflectionId);
    $selectStmt->execute();
    $updatedReflection = $selectStmt->fetch();
    
    jsonResponse(true, 'Reflection updated successfully', ['reflection' => $updatedReflection]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
