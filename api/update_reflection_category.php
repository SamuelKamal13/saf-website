<?php
/**
 * Update Reflection Category API Endpoint
 * Method: PUT/POST
 * Parameters: id, name_ar, name_en, description, icon, color, display_order, is_active
 * Returns: تفاصيل التصنيف المحدث
 * Auth: Admin only
 */

// Prevent PHP errors from showing as HTML
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod(['POST', 'PUT']);

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
$nameAr = sanitizeInput($data['name_ar'] ?? '');
$nameEn = sanitizeInput($data['name_en'] ?? '');
$description = sanitizeInput($data['description'] ?? null);
$icon = sanitizeInput($data['icon'] ?? '📖');
$color = sanitizeInput($data['color'] ?? '#8B0000');
$displayOrder = intval($data['display_order'] ?? 0);
$isActive = isset($data['is_active']) ? intval($data['is_active']) : 1;

// التحقق من المدخلات
if ($id <= 0) {
    jsonResponse(false, 'معرف التصنيف مطلوب');
}

if (empty($nameAr)) {
    jsonResponse(false, 'الاسم بالعربية مطلوب');
}

if (empty($nameEn)) {
    jsonResponse(false, 'الاسم بالإنجليزية مطلوب');
}

// التحقق من صيغة اللون
if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
    jsonResponse(false, 'صيغة اللون غير صحيحة. يجب أن يكون بصيغة #RRGGBB');
}

try {
    // التحقق من وجود التصنيف
    $stmt = $db->prepare("SELECT id FROM reflection_categories WHERE id = ?");
    $stmt->execute([$id]);
    
    if (!$stmt->fetch()) {
        jsonResponse(false, 'التصنيف غير موجود', null, 404);
    }
    
    // التحقق من عدم وجود تصنيف آخر بنفس الاسم
    $stmt = $db->prepare("SELECT id FROM reflection_categories WHERE (name_ar = ? OR name_en = ?) AND id != ?");
    $stmt->execute([$nameAr, $nameEn, $id]);
    
    if ($stmt->fetch()) {
        jsonResponse(false, 'يوجد تصنيف آخر بنفس الاسم');
    }
    
    // تحديث التصنيف
    $query = "UPDATE reflection_categories 
              SET name_ar = ?, 
                  name_en = ?, 
                  description = ?, 
                  icon = ?, 
                  color = ?, 
                  display_order = ?, 
                  is_active = ?
              WHERE id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$nameAr, $nameEn, $description, $icon, $color, $displayOrder, $isActive, $id]);
    
    // جلب التصنيف المحدث
    $stmt = $db->prepare("SELECT * FROM reflection_categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    
    jsonResponse(true, 'تم تحديث التصنيف بنجاح', [
        'category' => $category
    ]);
    
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        jsonResponse(false, 'يوجد تصنيف بنفس الاسم بالفعل', null, 409);
    }
    jsonResponse(false, 'خطأ في قاعدة البيانات: ' . $e->getMessage(), null, 500);
}
?>
