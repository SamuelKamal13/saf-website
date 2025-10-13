<?php
/**
 * Add Announcement API Endpoint
 * Method: POST
 * Parameters: title, content, author (optional)
 * Headers: Authorization: Bearer {token}
 * Required Role: admin or servant
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod('POST');

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

// Check if user has permission
if ($user['role'] != 'admin' && $user['role'] != 'servant') {
    jsonResponse(false, 'Insufficient permissions', null, 403);
}

$data = getJsonInput();

// Validate input
if (empty($data['title']) || empty($data['content'])) {
    jsonResponse(false, 'Title and content are required', null, 400);
}

$title = sanitizeInput($data['title']);
$content = sanitizeInput($data['content']);
$author = isset($data['author']) ? sanitizeInput($data['author']) : $user['name'];
$isPinned = isset($data['is_pinned']) ? ($data['is_pinned'] ? 1 : 0) : 0;

try {
    $query = "INSERT INTO announcements (title, content, author, is_pinned) 
              VALUES (:title, :content, :author, :is_pinned)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':author', $author);
    $stmt->bindParam(':is_pinned', $isPinned);
    
    if ($stmt->execute()) {
        $announcementId = $db->lastInsertId();
        
        // Get the created announcement
        $getQuery = "SELECT * FROM announcements WHERE id = :id";
        $getStmt = $db->prepare($getQuery);
        $getStmt->bindParam(':id', $announcementId);
        $getStmt->execute();
        $announcement = $getStmt->fetch();
        
        // إرسال إشعار تلقائي لجميع المستخدمين عن الإعلان الجديد
        try {
            $notifTitle = "📢 إعلان جديد: " . $title;
            $notifBody = $content;
            
            $notifData = json_encode([
                'type' => 'announcement',
                'announcement_id' => $announcementId,
                'is_pinned' => $isPinned
            ]);
            
            $priority = $isPinned ? 'high' : 'normal';
            
            $notifQuery = "INSERT INTO notifications 
                          (title, body, type, send_immediately, target_users, priority, data, created_by) 
                          VALUES 
                          (:title, :body, 'announcement', TRUE, 'all', :priority, :data, :created_by)";
            
            $notifStmt = $db->prepare($notifQuery);
            $notifStmt->bindParam(':title', $notifTitle);
            $notifStmt->bindParam(':body', $notifBody);
            $notifStmt->bindParam(':priority', $priority);
            $notifStmt->bindParam(':data', $notifData);
            $notifStmt->bindParam(':created_by', $user['id']);
            
            if ($notifStmt->execute()) {
                $notificationId = $db->lastInsertId();
                
                // إرسال الإشعار فوراً لجميع المستخدمين
                $usersQuery = "SELECT id FROM users WHERE is_active = 1";
                $usersStmt = $db->prepare($usersQuery);
                $usersStmt->execute();
                $users = $usersStmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($users as $userId) {
                    // إضافة إلى pending_notifications
                    $pendingQuery = "INSERT INTO pending_notifications (user_id, notification_id) 
                                    VALUES (:user_id, :notification_id)
                                    ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP";
                    $pendingStmt = $db->prepare($pendingQuery);
                    $pendingStmt->bindParam(':user_id', $userId);
                    $pendingStmt->bindParam(':notification_id', $notificationId);
                    $pendingStmt->execute();
                    
                    // إضافة إلى notification_logs
                    $logQuery = "INSERT INTO notification_logs (notification_id, user_id) 
                                VALUES (:notification_id, :user_id)
                                ON DUPLICATE KEY UPDATE sent_at = CURRENT_TIMESTAMP";
                    $logStmt = $db->prepare($logQuery);
                    $logStmt->bindParam(':notification_id', $notificationId);
                    $logStmt->bindParam(':user_id', $userId);
                    $logStmt->execute();
                }
                
                // تحديث حالة الإشعار
                $updateNotifQuery = "UPDATE notifications SET is_sent = TRUE, last_sent_at = NOW() WHERE id = :id";
                $updateNotifStmt = $db->prepare($updateNotifQuery);
                $updateNotifStmt->bindParam(':id', $notificationId);
                $updateNotifStmt->execute();
            }
        } catch (Exception $e) {
            // لا نريد فشل إضافة الإعلان إذا فشل الإشعار
            error_log("Failed to send notification: " . $e->getMessage());
        }
        
        jsonResponse(true, 'Announcement created successfully', ['announcement' => $announcement], 201);
    } else {
        jsonResponse(false, 'Failed to create announcement', null, 500);
    }
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
