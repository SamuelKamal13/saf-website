<?php
/**
 * Get Reflections API Endpoint
 * Method: GET
 * Parameters: limit (optional, default 50), category (optional)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod('GET');

$database = new Database();
$db = $database->getConnection();

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;

try {
    $query = "SELECT * FROM reflections";
    
    if ($category) {
        $query .= " WHERE category = :category";
    }
    
    $query .= " ORDER BY date DESC LIMIT :limit";
    
    $stmt = $db->prepare($query);
    
    if ($category) {
        $stmt->bindParam(':category', $category);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $reflections = $stmt->fetchAll();
    
    jsonResponse(true, 'Reflections retrieved', [
        'reflections' => $reflections,
        'count' => count($reflections)
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
