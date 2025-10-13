<?php
/**
 * Get Announcements API Endpoint
 * Method: GET
 * Parameters: limit (optional, default 50)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod('GET');

$database = new Database();
$db = $database->getConnection();

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

try {
    $query = "SELECT * FROM announcements 
              ORDER BY is_pinned DESC, date DESC 
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $announcements = $stmt->fetchAll();
    
    jsonResponse(true, 'Announcements retrieved', [
        'announcements' => $announcements,
        'count' => count($announcements)
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
