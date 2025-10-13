<?php
/**
 * حذف/تعطيل إشعار (للأدمن)
 * DELETE /api/delete_notification.php
 * 
 * Body:
 * {
 *   "id": 123
 * }
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once 'config/database.php';
require_once 'includes/functions.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    
    // قراءة البيانات
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
        exit;
    }
    
    $notification_id = $data->id;
    
    // حذف الإشعار
    $query = "DELETE FROM notifications WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $notification_id);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete notification');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
