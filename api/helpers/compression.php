<?php
/**
 * Response Compression Helper
 * ==========================
 * تفعيل ضغط GZIP لتقليل حجم البيانات المرسلة
 * 
 * الفوائد:
 * - تقليل حجم Response بنسبة 60-80%
 * - سرعة أكبر في نقل البيانات
 * - تقليل استهلاك الـ Bandwidth
 */

/**
 * تفعيل GZIP Compression
 * يجب استدعاء هذه الدالة في بداية كل API endpoint
 */
function enableCompression() {
    // التحقق من أن الـ Headers لم تُرسل بعد
    if (!headers_sent()) {
        // التحقق من دعم المتصفح لـ GZIP
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && 
            strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
            
            // تفعيل GZIP Compression
            if (!ob_start('ob_gzhandler')) {
                // إذا فشل GZIP، استخدم Output Buffering عادي
                ob_start();
            }
            
            // إضافة Header للـ Content Encoding
            header('Content-Encoding: gzip');
        } else {
            // إذا المتصفح لا يدعم GZIP، استخدم Output Buffering عادي
            ob_start();
        }
        
        // إضافة Headers إضافية للأداء
        header('Vary: Accept-Encoding');
        
        // التحقق من وجود ETag للـ Caching
        if (function_exists('header_register_callback')) {
            header_register_callback('addEtagHeader');
        }
    }
}

/**
 * إضافة ETag Header للـ Caching
 */
function addEtagHeader() {
    $output = ob_get_contents();
    if ($output !== false) {
        $etag = md5($output);
        header("ETag: \"{$etag}\"");
        
        // التحقق من If-None-Match Header
        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
            $clientEtag = trim($_SERVER['HTTP_IF_NONE_MATCH'], '"');
            if ($clientEtag === $etag) {
                header('HTTP/1.1 304 Not Modified');
                ob_end_clean();
                exit;
            }
        }
    }
}

/**
 * إضافة Cache Headers
 * 
 * @param int $maxAge عدد الثواني للـ Cache (default: 300 = 5 دقائق)
 */
function addCacheHeaders($maxAge = 300) {
    if (!headers_sent()) {
        header("Cache-Control: public, max-age={$maxAge}");
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
    }
}

/**
 * منع الـ Caching (للبيانات الحساسة)
 */
function preventCaching() {
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
    }
}

/**
 * إنهاء الـ Response وإرسالها
 */
function flushResponse() {
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    flush();
}

/**
 * مثال على الاستخدام:
 * ====================
 * 
 * <?php
 * require_once 'helpers/compression.php';
 * 
 * // في بداية كل API endpoint
 * enableCompression();
 * 
 * // للبيانات العامة (يمكن تخزينها في Cache)
 * addCacheHeaders(300); // Cache لمدة 5 دقائق
 * 
 * // أو للبيانات الحساسة (لا يجب تخزينها)
 * // preventCaching();
 * 
 * // ... باقي كود الـ API ...
 * 
 * echo json_encode($response);
 * 
 * // في النهاية
 * flushResponse();
 * ?>
 */
?>
