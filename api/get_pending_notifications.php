<?php
/**
 * جلب الإشعارات المعلقة للمستخدم
 * GET /api/get_pending_notifications.php
 * 
 * Headers:
 * Authorization: Bearer {token}
 * 
 * Response:
 * {
 *   "success": true,
 *   "notifications": [...]
 * }
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

try {
    // التحقق من المستخدم باستخدام دالة validateToken
    $user = validateToken($db, $token);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }
    
    $user_id = $user['id'];
    
    // جلب الإشعارات المعلقة
    $query = "SELECT 
                n.id,
                n.title,
                n.body,
                n.type,
                n.priority,
                n.data,
                n.created_at,
                pn.created_at as received_at
              FROM pending_notifications pn
              JOIN notifications n ON pn.notification_id = n.id
              WHERE pn.user_id = :user_id
              ORDER BY n.priority DESC, pn.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $notifications = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // فك تشفير البيانات الإضافية إذا وجدت
        if ($row['data']) {
            $row['data'] = json_decode($row['data'], true);
        }
        $notifications[] = $row;
    }
    
    // حذف الإشعارات المعلقة بعد جلبها (تم استلامها)
    if (count($notifications) > 0) {
        $query = "DELETE FROM pending_notifications WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'count' => count($notifications)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
