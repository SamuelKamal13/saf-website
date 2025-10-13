<?php
/**
 * Delete Reflection Category API Endpoint
 * Method: POST/DELETE
 * Parameters: id
 * Returns: رسالة نجاح
 * Auth: Admin only
 */

// Prevent PHP errors from showing as HTML
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod(['POST', 'DELETE']);

$database = new Database();
$db = $database->getConnection();

// التحقق من صلاحيات المسؤول
$token = getBearerToken();
if (!$token) {
    jsonResponse(false, 'Authentication required', null, 401);
}

$user = validateToken($db, $token);
if (!$user) {
    jsonResponse(false, 'Invalid or expired token', null, 401);
}

// التحقق من أن المستخدم مسؤول
if ($user['role'] !== 'admin') {
    jsonResponse(false, 'Admin access required', null, 403);
}

// قراءة البيانات
$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id'] ?? 0);

// التحقق من المدخلات
if ($id <= 0) {
    jsonResponse(false, 'معرف التصنيف مطلوب');
}

try {
    // التحقق من وجود التصنيف
    $stmt = $db->prepare("SELECT name_ar, name_en FROM reflection_categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        jsonResponse(false, 'التصنيف غير موجود', null, 404);
    }
    
    // التحقق من عدم وجود تأملات مرتبطة بهذا التصنيف
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM reflections WHERE category_id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        jsonResponse(false, "لا يمكن حذف التصنيف لأنه مرتبط بـ {$result['count']} تأمل. قم بحذف أو نقل التأملات أولاً.");
    }
    
    // حذف التصنيف
    $stmt = $db->prepare("DELETE FROM reflection_categories WHERE id = ?");
    $stmt->execute([$id]);
    
    jsonResponse(true, "تم حذف التصنيف '{$category['name_ar']}' بنجاح", [
        'deleted_category' => $category
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'خطأ في قاعدة البيانات: ' . $e->getMessage(), null, 500);
}
?>
