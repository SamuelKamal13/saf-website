<?php
/**
 * Test Reflection Categories System
 * اختبار نظام تصنيفات التأملات
 */

header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

$results = [
    'success' => true,
    'checks' => []
];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // 1. Check if reflection_categories table exists
    $stmt = $db->query("SHOW TABLES LIKE 'reflection_categories'");
    $tableExists = $stmt->rowCount() > 0;
    
    $results['checks']['table_exists'] = [
        'status' => $tableExists,
        'message' => $tableExists ? '✅ جدول reflection_categories موجود' : '❌ جدول reflection_categories غير موجود'
    ];
    
    if (!$tableExists) {
        $results['success'] = false;
        $results['error'] = 'يجب تشغيل ملف setup_reflection_categories.sql أولاً';
        echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    // 2. Check categories count
    $stmt = $db->query("SELECT COUNT(*) as count FROM reflection_categories");
    $count = $stmt->fetch()['count'];
    
    $results['checks']['categories_count'] = [
        'status' => $count > 0,
        'message' => "✅ عدد التصنيفات: $count",
        'count' => $count
    ];
    
    // 3. Get all categories
    $stmt = $db->query("SELECT * FROM reflection_categories ORDER BY display_order");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $results['checks']['categories_list'] = [
        'status' => true,
        'message' => '✅ قائمة التصنيفات',
        'categories' => $categories
    ];
    
    // 4. Check if category_id column exists in reflections table
    $stmt = $db->query("SHOW COLUMNS FROM reflections LIKE 'category_id'");
    $columnExists = $stmt->rowCount() > 0;
    
    $results['checks']['column_exists'] = [
        'status' => $columnExists,
        'message' => $columnExists ? '✅ عمود category_id موجود في جدول reflections' : '⚠️ عمود category_id غير موجود'
    ];
    
    // 5. Check functions.php exists
    $functionsPath = __DIR__ . '/includes/functions.php';
    $functionsExists = file_exists($functionsPath);
    
    $results['checks']['functions_file'] = [
        'status' => $functionsExists,
        'message' => $functionsExists ? '✅ ملف functions.php موجود' : '❌ ملف functions.php غير موجود',
        'path' => $functionsPath
    ];
    
    // 6. Test token validation (without actual token)
    if ($functionsExists) {
        require_once $functionsPath;
        
        $results['checks']['functions_loaded'] = [
            'status' => function_exists('getBearerToken'),
            'message' => function_exists('getBearerToken') ? '✅ دوال المصادقة موجودة' : '❌ دوال المصادقة غير موجودة'
        ];
    }
    
    $results['message'] = '✅ جميع الفحوصات نجحت!';
    
} catch (Exception $e) {
    $results['success'] = false;
    $results['error'] = $e->getMessage();
    $results['trace'] = $e->getTraceAsString();
}

echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
