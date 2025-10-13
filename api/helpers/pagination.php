<?php
/**
 * Pagination Helper
 * ================
 * مساعد لتقسيم النتائج وتحسين الأداء
 * 
 * الفوائد:
 * - تقليل حجم البيانات المرسلة
 * - تحسين سرعة الاستجابة
 * - تقليل استهلاك الذاكرة
 */

/**
 * الحصول على معاملات الـ Pagination من الـ Request
 * 
 * @return array ['page' => int, 'limit' => int, 'offset' => int]
 */
function getPaginationParams() {
    // الصفحة الحالية (default: 1)
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    
    // عدد العناصر في الصفحة (default: 50, max: 100, min: 10)
    $limit = isset($_GET['limit']) ? min(100, max(10, (int)$_GET['limit'])) : 50;
    
    // حساب الـ Offset
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset
    ];
}

/**
 * إضافة LIMIT و OFFSET إلى الاستعلام
 * 
 * @param string $baseQuery الاستعلام الأساسي
 * @param array $params معاملات الـ Pagination
 * @return string الاستعلام مع LIMIT و OFFSET
 */
function addPaginationToQuery($baseQuery, $params) {
    return $baseQuery . " LIMIT {$params['limit']} OFFSET {$params['offset']}";
}

/**
 * الحصول على العدد الإجمالي للسجلات
 * 
 * @param PDO $db اتصال قاعدة البيانات
 * @param string $table اسم الجدول أو الـ View
 * @param string $whereClause شرط WHERE (اختياري)
 * @param array $params معاملات الـ WHERE (اختياري)
 * @return int العدد الإجمالي
 */
function getTotalCount($db, $table, $whereClause = '', $params = []) {
    $query = "SELECT COUNT(*) as total FROM {$table}";
    
    if (!empty($whereClause)) {
        $query .= " WHERE {$whereClause}";
    }
    
    $stmt = $db->prepare($query);
    
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)$result['total'];
}

/**
 * بناء معلومات الـ Pagination للاستجابة
 * 
 * @param int $total العدد الإجمالي للسجلات
 * @param array $params معاملات الـ Pagination
 * @return array معلومات الـ Pagination
 */
function buildPaginationInfo($total, $params) {
    $totalPages = ceil($total / $params['limit']);
    
    return [
        'current_page' => $params['page'],
        'per_page' => $params['limit'],
        'total_records' => $total,
        'total_pages' => $totalPages,
        'has_next_page' => $params['page'] < $totalPages,
        'has_previous_page' => $params['page'] > 1,
        'next_page' => $params['page'] < $totalPages ? $params['page'] + 1 : null,
        'previous_page' => $params['page'] > 1 ? $params['page'] - 1 : null
    ];
}

/**
 * بناء استجابة JSON كاملة مع Pagination
 * 
 * @param array $data البيانات
 * @param int $total العدد الإجمالي
 * @param array $paginationParams معاملات الـ Pagination
 * @return array الاستجابة الكاملة
 */
function buildPaginatedResponse($data, $total, $paginationParams) {
    return [
        'success' => true,
        'data' => $data,
        'pagination' => buildPaginationInfo($total, $paginationParams)
    ];
}

/**
 * مثال على الاستخدام:
 * ====================
 * 
 * require_once 'helpers/pagination.php';
 * 
 * // 1. الحصول على معاملات الـ Pagination
 * $pagination = getPaginationParams();
 * 
 * // 2. الحصول على العدد الإجمالي
 * $total = getTotalCount($db, 'attendance_view');
 * 
 * // 3. بناء الاستعلام مع Pagination
 * $query = "SELECT * FROM attendance_view ORDER BY timestamp DESC";
 * $query = addPaginationToQuery($query, $pagination);
 * 
 * // 4. تنفيذ الاستعلام
 * $stmt = $db->prepare($query);
 * $stmt->execute();
 * $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
 * 
 * // 5. إرسال الاستجابة
 * $response = buildPaginatedResponse($data, $total, $pagination);
 * echo json_encode($response, JSON_UNESCAPED_UNICODE);
 */
?>
