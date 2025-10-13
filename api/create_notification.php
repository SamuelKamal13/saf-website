<?php
/**
 * إنشاء إشعار جديد (للأدمن فقط)
 * POST /api/create_notification.php
 * 
 * Body:
 * {
 *   "title": "عنوان الإشعار",
 *   "body": "محتوى الإشعار",
 *   "type": "custom|reflection|announcement|event|system",
 *   "send_immediately": true|false,
 *   "scheduled_at": "2024-01-01 10:00:00" (optional),
 *   "repeat_type": "none|daily|weekly|monthly",
 *   "target_users": "all|members|servants|admins|specific",
 *   "specific_user_ids": [1,2,3] (optional, if target_users='specific'),
 *   "priority": "low|normal|high",
 *   "data": {"link": "...", "image": "..."} (optional)
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

// التحقق من التوكن وأنه أدمن باستخدام دالة validateToken
$user = validateToken($db, $token);

if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden - Admin access required']);
    exit;
}

// قراءة البيانات
$data = json_decode(file_get_contents("php://input"));

// التحقق من البيانات المطلوبة
if (!isset($data->title) || !isset($data->body)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Title and body are required']);
    exit;
}

try {
    $title = $data->title;
    $body = $data->body;
    $type = isset($data->type) ? $data->type : 'custom';
    $send_immediately = isset($data->send_immediately) ? (bool)$data->send_immediately : true;
    $scheduled_at = isset($data->scheduled_at) ? $data->scheduled_at : null;
    $repeat_type = isset($data->repeat_type) ? $data->repeat_type : 'none';
    $target_users = isset($data->target_users) ? $data->target_users : 'all';
    $specific_user_ids = isset($data->specific_user_ids) ? json_encode($data->specific_user_ids) : null;
    $priority = isset($data->priority) ? $data->priority : 'normal';
    $extra_data = isset($data->data) ? json_encode($data->data) : null;
    
    // حساب next_send_at بناءً على scheduled_at و repeat_type
    $next_send_at = null;
    if (!$send_immediately && $scheduled_at) {
        $next_send_at = $scheduled_at;
    }
    
    // إنشاء الإشعار
    $query = "INSERT INTO notifications 
              (title, body, type, send_immediately, scheduled_at, repeat_type, 
               target_users, specific_user_ids, priority, data, created_by, next_send_at) 
              VALUES 
              (:title, :body, :type, :send_immediately, :scheduled_at, :repeat_type, 
               :target_users, :specific_user_ids, :priority, :data, :created_by, :next_send_at)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':body', $body);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':send_immediately', $send_immediately, PDO::PARAM_BOOL);
    $stmt->bindParam(':scheduled_at', $scheduled_at);
    $stmt->bindParam(':repeat_type', $repeat_type);
    $stmt->bindParam(':target_users', $target_users);
    $stmt->bindParam(':specific_user_ids', $specific_user_ids);
    $stmt->bindParam(':priority', $priority);
    $stmt->bindParam(':data', $extra_data);
    $stmt->bindParam(':created_by', $user['id']);
    $stmt->bindParam(':next_send_at', $next_send_at);
    
    if ($stmt->execute()) {
        $notification_id = $db->lastInsertId();
        
        // إذا كان الإرسال فورياً، أرسل الآن
        if ($send_immediately) {
            $sent = sendNotificationToUsers($db, $notification_id);
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Notification created and sent successfully',
                'notification_id' => $notification_id,
                'sent_to' => $sent
            ]);
        } else {
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Notification scheduled successfully',
                'notification_id' => $notification_id,
                'scheduled_at' => $scheduled_at
            ]);
        }
    } else {
        throw new Exception('Failed to create notification');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

/**
 * إرسال الإشعار للمستخدمين المستهدفين
 */
function sendNotificationToUsers($db, $notification_id) {
    // جلب معلومات الإشعار
    $query = "SELECT * FROM notifications WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $notification_id);
    $stmt->execute();
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$notification) {
        return 0;
    }
    
    // تحديد المستخدمين المستهدفين
    $target_users = $notification['target_users'];
    $user_ids = [];
    
    if ($target_users === 'specific' && $notification['specific_user_ids']) {
        $user_ids = json_decode($notification['specific_user_ids'], true);
    } else {
        // جلب المستخدمين حسب الفئة
        $where = "is_active = 1";
        
        if ($target_users === 'members') {
            $where .= " AND role = 'member'";
        } elseif ($target_users === 'servants') {
            $where .= " AND role = 'servant'";
        } elseif ($target_users === 'admins') {
            $where .= " AND role = 'admin'";
        }
        // إذا كان 'all' لا نضيف شرط إضافي
        
        $query = "SELECT id FROM users WHERE $where";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $user_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // إضافة الإشعارات المعلقة لكل مستخدم
    $sent_count = 0;
    foreach ($user_ids as $user_id) {
        try {
            // إضافة إلى pending_notifications
            $query = "INSERT INTO pending_notifications (user_id, notification_id) 
                      VALUES (:user_id, :notification_id)
                      ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':notification_id', $notification_id);
            $stmt->execute();
            
            // إضافة إلى notification_logs
            $query = "INSERT INTO notification_logs (notification_id, user_id) 
                      VALUES (:notification_id, :user_id)
                      ON DUPLICATE KEY UPDATE sent_at = CURRENT_TIMESTAMP";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':notification_id', $notification_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $sent_count++;
        } catch (Exception $e) {
            // تجاهل الأخطاء المتكررة
            continue;
        }
    }
    
    // تحديث حالة الإشعار
    $now = date('Y-m-d H:i:s');
    $query = "UPDATE notifications 
              SET is_sent = TRUE, last_sent_at = :now";
    
    // إذا كان متكرر، حساب next_send_at
    if ($notification['repeat_type'] !== 'none') {
        $next_send = calculateNextSendTime($now, $notification['repeat_type']);
        $query .= ", next_send_at = :next_send";
    } else {
        $query .= ", next_send_at = NULL";
    }
    
    $query .= " WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':now', $now);
    $stmt->bindParam(':id', $notification_id);
    
    if ($notification['repeat_type'] !== 'none') {
        $stmt->bindParam(':next_send', $next_send);
    }
    
    $stmt->execute();
    
    return $sent_count;
}

/**
 * حساب موعد الإرسال القادم للإشعارات المتكررة
 */
function calculateNextSendTime($current_time, $repeat_type) {
    $timestamp = strtotime($current_time);
    
    switch ($repeat_type) {
        case 'daily':
            $next = strtotime('+1 day', $timestamp);
            break;
        case 'weekly':
            $next = strtotime('+1 week', $timestamp);
            break;
        case 'monthly':
            $next = strtotime('+1 month', $timestamp);
            break;
        default:
            return null;
    }
    
    return date('Y-m-d H:i:s', $next);
}
?>
