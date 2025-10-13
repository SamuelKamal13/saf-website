<?php
/**
 * Get Reflection Categories API Endpoint
 * Method: GET
 * Parameters: active_only (optional, default false)
 * Returns: قائمة تصنيفات التأملات
 */

// Prevent PHP errors from showing as HTML
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod('GET');

$database = new Database();
$db = $database->getConnection();

$activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';

try {
    $query = "SELECT 
                id,
                name_ar,
                name_en,
                description,
                icon,
                color,
                display_order,
                is_active,
                created_at,
                updated_at
              FROM reflection_categories";
    
    if ($activeOnly) {
        $query .= " WHERE is_active = 1";
    }
    
    $query .= " ORDER BY display_order ASC, name_ar ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $categories = $stmt->fetchAll();
    
    jsonResponse(true, 'Categories retrieved successfully', [
        'categories' => $categories,
        'count' => count($categories)
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
