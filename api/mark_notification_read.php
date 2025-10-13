<?php
/**
 * تحديث حالة قراءة الإشعار
 * POST /api/mark_notification_read.php
 * 
 * Body:
 * {
 *   "notification_id": 123
 * }
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once 'config/database.php';
require_once 'includes/functions.php';

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    
    // قراءة البيانات
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->notification_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
        exit;
    }
    
    $notification_id = $data->notification_id;
    
    // تحديث حالة القراءة
    $now = date('Y-m-d H:i:s');
    $query = "UPDATE notification_logs 
              SET is_read = TRUE, read_at = :read_at 
              WHERE notification_id = :notification_id AND user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':read_at', $now);
    $stmt->bindParam(':notification_id', $notification_id);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    } else {
        throw new Exception('Failed to update notification status');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
