<?php
/**
 * Get Shared Barcodes API Endpoint
 * Method: GET
 * Returns all shared barcodes for event types
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

try {
    // Get all shared barcodes with statistics
    $query = "SELECT 
                etb.id,
                etb.event_type,
                etb.barcode,
                etb.arabic_name,
                etb.description,
                etb.is_active,
                COUNT(DISTINCT e.id) as total_events,
                COUNT(DISTINCT a.user_id) as total_unique_attendees,
                COUNT(a.id) as total_attendance_records,
                MAX(a.timestamp) as last_scan_time
              FROM event_type_barcodes etb
              LEFT JOIN events e ON e.shared_barcode = etb.barcode
              LEFT JOIN attendance a ON e.id = a.event_id
              GROUP BY etb.id, etb.event_type, etb.barcode, etb.arabic_name, etb.description, etb.is_active
              ORDER BY etb.event_type";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $sharedBarcodes = $stmt->fetchAll();
    
    jsonResponse(true, 'Shared barcodes retrieved successfully', [
        'shared_barcodes' => $sharedBarcodes,
        'count' => count($sharedBarcodes)
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
