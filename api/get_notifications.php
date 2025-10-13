<?php
/**
 * جلب قائمة جميع الإشعارات (للأدمن)
 * GET /api/get_notifications.php
 * 
 * Query params:
 * - status: all|sent|scheduled
 * - type: all|custom|reflection|announcement|event|system
 * - limit: عدد النتائج (افتراضي 50)
 * - offset: البداية (افتراضي 0)
 * 
 * Headers:
 * Authorization: Bearer {admin_token}
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once 'config/database.php';
require_once 'includes/functions.php';

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// التحقق من صلاحيات الأدمن
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Token required']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // التحقق من التوكن وأنه أدمن باستخدام دالة validateToken
    $user = validateToken($db, $token);
    
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden - Admin access required']);
        exit;
    }
    
    // معالجة المعاملات
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    // بناء الاستعلام
    $where_clauses = ['1=1'];
    $params = [];
    
    if ($status === 'sent') {
        $where_clauses[] = "is_sent = TRUE";
    } elseif ($status === 'scheduled') {
        $where_clauses[] = "is_sent = FALSE AND scheduled_at IS NOT NULL";
    }
    
    if ($type !== 'all') {
        $where_clauses[] = "type = :type";
        $params[':type'] = $type;
    }
    
    $where_sql = implode(' AND ', $where_clauses);
    
    // جلب الإشعارات
    $query = "SELECT 
                n.*,
                u.name as created_by_name,
                (SELECT COUNT(*) FROM notification_logs nl WHERE nl.notification_id = n.id) as sent_count,
                (SELECT COUNT(*) FROM notification_logs nl WHERE nl.notification_id = n.id AND nl.is_read = TRUE) as read_count
              FROM notifications n
              LEFT JOIN users u ON n.created_by = u.id
              WHERE $where_sql
              ORDER BY n.created_at DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $notifications = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // فك تشفير JSON
        if ($row['specific_user_ids']) {
            $row['specific_user_ids'] = json_decode($row['specific_user_ids'], true);
        }
        if ($row['data']) {
            $row['data'] = json_decode($row['data'], true);
        }
        $notifications[] = $row;
    }
    
    // إحصائيات عامة
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_sent = TRUE THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN is_sent = FALSE AND scheduled_at IS NOT NULL THEN 1 ELSE 0 END) as scheduled,
                SUM(CASE WHEN repeat_type != 'none' THEN 1 ELSE 0 END) as recurring
              FROM notifications";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'count' => count($notifications),
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
