<?php
/**
 * Get Events API Endpoint
 * Method: GET
 * Parameters: type (optional), from_date (optional), to_date (optional), limit (optional)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod('GET');

$database = new Database();
$db = $database->getConnection();

$conditions = ['is_active = 1'];
$params = [];

// Filter by type
if (isset($_GET['type'])) {
    $conditions[] = "type = :type";
    $params[':type'] = $_GET['type'];
}

// Filter by date range
if (isset($_GET['from_date'])) {
    $conditions[] = "DATE(date) >= :from_date";
    $params[':from_date'] = $_GET['from_date'];
}

if (isset($_GET['to_date'])) {
    $conditions[] = "DATE(date) <= :to_date";
    $params[':to_date'] = $_GET['to_date'];
}

$whereClause = 'WHERE ' . implode(' AND ', $conditions);
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

try {
    $query = "SELECT * FROM events 
              {$whereClause}
              ORDER BY date DESC 
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $events = $stmt->fetchAll();
    
    jsonResponse(true, 'Events retrieved', [
        'events' => $events,
        'count' => count($events)
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
