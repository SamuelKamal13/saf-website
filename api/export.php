<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Validate authentication - support both Bearer token and URL parameter
    $token = getBearerToken();
    
    // If no Bearer token, check for token in URL parameter
    if (!$token && isset($_GET['token'])) {
        $token = $_GET['token'];
    }
    
    if (!$token) {
        throw new Exception('Authentication required');
    }
    
    $user = validateToken($db, $token);
    if (!$user) {
        throw new Exception('Invalid or expired token');
    }
    
    // Only admins and servants can export
    if ($user['role'] !== 'admin' && $user['role'] !== 'servant') {
        throw new Exception('Permission denied. Only admins and servants can export data.');
    }
    
    // Get parameters
    $format = $_GET['format'] ?? 'csv'; // csv or json
    $type = $_GET['type'] ?? 'attendance'; // attendance, users, events
    $from_date = $_GET['from_date'] ?? null;
    $to_date = $_GET['to_date'] ?? null;
    $event_type = $_GET['event_type'] ?? null;
    
    $data = [];
    $filename = '';
    
    switch ($type) {
        case 'attendance':
            $query = "SELECT 
                        a.id,
                        a.timestamp,
                        a.status,
                        u.name as user_name,
                        u.email as user_email,
                        u.barcode_id,
                        e.name as event_name,
                        e.type as event_type,
                        e.date as event_date,
                        e.location
                      FROM attendance a
                      JOIN users u ON a.user_id = u.id
                      JOIN events e ON a.event_id = e.id
                      WHERE 1=1";
            
            if ($from_date) {
                $query .= " AND DATE(a.timestamp) >= :from_date";
            }
            if ($to_date) {
                $query .= " AND DATE(a.timestamp) <= :to_date";
            }
            if ($event_type) {
                $query .= " AND e.type = :event_type";
            }
            
            $query .= " ORDER BY a.timestamp DESC";
            
            $stmt = $db->prepare($query);
            if ($from_date) $stmt->bindParam(':from_date', $from_date);
            if ($to_date) $stmt->bindParam(':to_date', $to_date);
            if ($event_type) $stmt->bindParam(':event_type', $event_type);
            $stmt->execute();
            
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $filename = 'attendance_report_' . date('Y-m-d');
            break;
            
        case 'users':
            $query = "SELECT id, name, email, role, barcode_id, phone, created_at 
                      FROM users 
                      WHERE is_active = 1
                      ORDER BY name ASC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $filename = 'users_report_' . date('Y-m-d');
            break;
            
        case 'events':
            $query = "SELECT 
                        e.id,
                        e.name,
                        e.type,
                        e.date,
                        e.location,
                        e.barcode,
                        COUNT(a.id) as total_attendance,
                        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                        SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused_count
                      FROM events e
                      LEFT JOIN attendance a ON e.id = a.event_id
                      WHERE e.is_active = 1
                      GROUP BY e.id
                      ORDER BY e.date DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $filename = 'events_report_' . date('Y-m-d');
            break;
            
        default:
            throw new Exception('Invalid export type');
    }
    
    // Export based on format
    if ($format === 'csv') {
        // Set CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            
            // Write data
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit();
        
    } elseif ($format === 'json') {
        // JSON export
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        
        echo json_encode([
            'success' => true,
            'exported_at' => date('Y-m-d H:i:s'),
            'total_records' => count($data),
            'data' => $data
        ], JSON_PRETTY_PRINT);
        exit();
        
    } else {
        throw new Exception('Invalid export format. Use csv or json.');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    jsonResponse(false, $e->getMessage());
}
?>
