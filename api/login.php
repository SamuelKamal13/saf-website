<?php
/**
 * Login API Endpoint
 * Method: POST
 * Parameters: email, password
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod('POST');

$database = new Database();
$db = $database->getConnection();

$data = getJsonInput();

// Validate input
if (empty($data['email']) || empty($data['password'])) {
    jsonResponse(false, 'Email and password are required', null, 400);
}

$email = sanitizeInput($data['email']);
$password = $data['password'];

try {
    // Check if user exists
    $query = "SELECT * FROM users WHERE email = :email AND is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        jsonResponse(false, 'Invalid email or password', null, 401);
    }
    
    $user = $stmt->fetch();
    
    // Verify password (supports both MD5 legacy and bcrypt)
    $passwordValid = false;
    if (strlen($user['password']) == 32) {
        // MD5 hash (legacy)
        $passwordValid = (md5($password) === $user['password']);
    } else {
        // bcrypt hash
        $passwordValid = verifyPassword($password, $user['password']);
    }
    
    if (!$passwordValid) {
        jsonResponse(false, 'Invalid email or password', null, 401);
    }
    
    // Generate new token
    $token = generateToken();
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . TOKEN_EXPIRY_HOURS . ' hours'));
    
    // Delete old tokens for this user
    $deleteQuery = "DELETE FROM sessions WHERE user_id = :user_id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':user_id', $user['id']);
    $deleteStmt->execute();
    
    // Insert new token
    $insertQuery = "INSERT INTO sessions (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':user_id', $user['id']);
    $insertStmt->bindParam(':token', $token);
    $insertStmt->bindParam(':expires_at', $expiresAt);
    $insertStmt->execute();
    
    // Remove password from response
    unset($user['password']);
    
    // Return user data with token
    jsonResponse(true, 'Login successful', [
        'user' => $user,
        'token' => $token,
        'expires_at' => $expiresAt
    ]);
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
