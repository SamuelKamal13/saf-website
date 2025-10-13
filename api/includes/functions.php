<?php
/**
 * Helper Functions for API
 */

// Generate authentication token
function generateToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

// Validate email format
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Hash password using bcrypt (more secure than MD5)
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// JSON response helper
function jsonResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit();
}

// Get authorization token from header
function getBearerToken() {
    $headers = getAuthorizationHeader();
    
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

// Get authorization header
function getAuthorizationHeader() {
    $headers = null;
    
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } else if (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(
            array_map('ucwords', array_keys($requestHeaders)), 
            array_values($requestHeaders)
        );
        
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    
    return $headers;
}

// Validate token and get user
function validateToken($db, $token) {
    try {
        $query = "SELECT s.user_id, u.* 
                  FROM sessions s 
                  JOIN users u ON s.user_id = u.id 
                  WHERE s.token = :token 
                  AND s.expires_at > NOW() 
                  AND u.is_active = 1";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
    } catch (PDOException $e) {
        return false;
    }
    
    return false;
}

// Generate unique barcode ID
function generateBarcodeId($prefix = 'USER') {
    return $prefix . '_' . strtoupper(uniqid()) . '_' . rand(1000, 9999);
}

// Generate event barcode
function generateEventBarcode($type, $date) {
    $type = strtoupper($type);
    $dateStr = date('Ymd', strtotime($date));
    $random = rand(100, 999);
    return "{$type}_{$dateStr}_{$random}";
}

// Format datetime for display
function formatDateTime($datetime) {
    return date('Y-m-d H:i:s', strtotime($datetime));
}

// Check if request method matches
function checkRequestMethod($method) {
    $allowedMethods = is_array($method) ? $method : [$method];
    
    if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
        $expected = implode(' or ', $allowedMethods);
        jsonResponse(false, "Invalid request method. Expected {$expected}", null, 405);
    }
}

// Get POST data as JSON
function getJsonInput() {
    $data = json_decode(file_get_contents("php://input"), true);
    return $data ? $data : [];
}
?>
