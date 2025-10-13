<?php
/**
 * جلب جميع إشعارات المستخدم (المقروءة وغير المقروءة)
 * GET /api/get_user_notifications.php
 * 
 * Query params:
 * - status: 'all' | 'read' | 'unread' (optional, default: 'all')
 * - limit: int (optional, default: 50)
 * - offset: int (optional, default: 0)
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

// التحقق من التوكن
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Token required']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// التحقق من صحة التوكن
$user = validateToken($db, $token);

if (!$user) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden - Invalid token']);
    exit;
}

try {
    $user_id = $user['id'];
    
    // قراءة المعاملات
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // بناء الاستعلام الأساسي
    $query = "SELECT 
                n.id,
                n.title,
                n.body,
                n.type,
                n.priority,
                n.data,
                n.created_at,
                nl.sent_at,
                nl.read_at,
                nl.is_read
              FROM notification_logs nl
              JOIN notifications n ON nl.notification_id = n.id
              WHERE nl.user_id = :user_id";
    
    // إضافة شرط الحالة
    if ($status === 'read') {
        $query .= " AND nl.is_read = 1";
    } elseif ($status === 'unread') {
        $query .= " AND nl.is_read = 0";
    }
    
    // ترتيب وتحديد
    $query .= " ORDER BY nl.sent_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // إحصائيات الإشعارات
    $stats_query = "SELECT 
                      COUNT(*) as total,
                      SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count,
                      SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count
                    FROM notification_logs
                    WHERE user_id = :user_id";
    
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    // تنسيق البيانات
    foreach ($notifications as &$notification) {
        $notification['is_read'] = (bool)$notification['is_read'];
        
        // فك تشفير JSON data إذا وجد
        if ($notification['data']) {
            $notification['data'] = json_decode($notification['data'], true);
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'stats' => [
            'total' => (int)$stats['total'],
            'read' => (int)$stats['read_count'],
            'unread' => (int)$stats['unread_count']
        ],
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
