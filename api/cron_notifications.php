<?php
/**
 * Cron Job للإشعارات المجدولة
 * يجب تشغيله كل دقيقة:
 * * * * * * php /path/to/api/cron_notifications.php
 * 
 * أو استخدام wget/curl:
 * * * * * * curl http://localhost/api/cron_notifications.php
 */

// منع الوصول المباشر من المتصفح (اختياري)
if (php_sapi_name() !== 'cli' && !isset($_GET['secret_key'])) {
    // يمكنك إضافة مفتاح سري للحماية
    // مثال: http://localhost/api/cron_notifications.php?secret_key=your_secret_key
    // die('Access denied');
}

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $now = date('Y-m-d H:i:s');
    
    echo "=== Notification Cron Job Started at $now ===\n";
    
    // البحث عن الإشعارات المجدولة التي حان موعدها
    $query = "SELECT * FROM notifications 
              WHERE is_active = TRUE 
              AND next_send_at IS NOT NULL 
              AND next_send_at <= :now
              ORDER BY next_send_at ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':now', $now);
    $stmt->execute();
    
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($notifications);
    
    echo "Found $count notification(s) to send\n";
    
    foreach ($notifications as $notification) {
        echo "\nProcessing notification #{$notification['id']}: {$notification['title']}\n";
        
        try {
            // إرسال الإشعار
            $sent_count = sendNotificationToUsers($db, $notification['id']);
            echo "✓ Sent to $sent_count user(s)\n";
            
            // تحديث حالة الإشعار
            updateNotificationStatus($db, $notification);
            
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Cron Job Completed ===\n";
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
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
    
    return $sent_count;
}

/**
 * تحديث حالة الإشعار بعد الإرسال
 */
function updateNotificationStatus($db, $notification) {
    $now = date('Y-m-d H:i:s');
    $notification_id = $notification['id'];
    
    // إذا كان الإشعار متكرر
    if ($notification['repeat_type'] !== 'none') {
        // حساب موعد الإرسال القادم
        $next_send = calculateNextSendTime($now, $notification['repeat_type']);
        
        $query = "UPDATE notifications 
                  SET is_sent = TRUE, 
                      last_sent_at = :now, 
                      next_send_at = :next_send 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':now', $now);
        $stmt->bindParam(':next_send', $next_send);
        $stmt->bindParam(':id', $notification_id);
        
        echo "Next send scheduled at: $next_send\n";
    } else {
        // إشعار غير متكرر - تم إرساله مرة واحدة
        $query = "UPDATE notifications 
                  SET is_sent = TRUE, 
                      last_sent_at = :now, 
                      next_send_at = NULL,
                      is_active = FALSE
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':now', $now);
        $stmt->bindParam(':id', $notification_id);
        
        echo "One-time notification completed\n";
    }
    
    $stmt->execute();
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
