<?php
/**
 * Get Attendance Records API Endpoint
 * Method: GET
 * Parameters: user_id (optional), event_id (optional), from_date (optional), to_date (optional)
 * Headers: Authorization: Bearer {token}
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/helpers/pagination.php';
require_once __DIR__ . '/helpers/compression.php';

// Enable compression for better performance
enableCompression();

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

// Build query based on parameters
$conditions = [];
$params = [];

// Filter by user_id
if (isset($_GET['user_id'])) {
    $conditions[] = "a.user_id = :user_id";
    $params[':user_id'] = $_GET['user_id'];
} else if ($user['role'] == 'member') {
    // Regular members can only see their own attendance
    $conditions[] = "a.user_id = :user_id";
    $params[':user_id'] = $user['id'];
}
// Admin and servants can see all attendance records

// Filter by event_id
if (isset($_GET['event_id'])) {
    $conditions[] = "a.event_id = :event_id";
    $params[':event_id'] = $_GET['event_id'];
}

// Filter by date range
if (isset($_GET['from_date'])) {
    $conditions[] = "DATE(a.timestamp) >= :from_date";
    $params[':from_date'] = $_GET['from_date'];
}

if (isset($_GET['to_date'])) {
    $conditions[] = "DATE(a.timestamp) <= :to_date";
    $params[':to_date'] = $_GET['to_date'];
}

$whereClause = '';
if (count($conditions) > 0) {
    $whereClause = 'WHERE ' . implode(' AND ', $conditions);
}

try {
    // Get pagination parameters
    $pagination = getPaginationParams();
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total 
                   FROM attendance a 
                   JOIN users u ON a.user_id = u.id 
                   JOIN events e ON a.event_id = e.id 
                   {$whereClause}";
    
    $countStmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value);
    }
    $countStmt->execute();
    $totalCount = (int)$countStmt->fetch()['total'];
    
    // Build query with pagination
    $query = "SELECT a.*, 
              u.name as user_name, u.email as user_email, u.barcode_id as user_barcode,
              e.name as event_name, e.type as event_type, e.date as event_date, e.location as event_location
              FROM attendance a 
              JOIN users u ON a.user_id = u.id 
              JOIN events e ON a.event_id = e.id 
              {$whereClause}
              ORDER BY a.timestamp DESC
              LIMIT {$pagination['limit']} OFFSET {$pagination['offset']}";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $attendances = $stmt->fetchAll();
    
    // Build paginated response
    $response = buildPaginatedResponse($attendances, $totalCount, $pagination);
    $response['message'] = 'Attendance records retrieved';
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
