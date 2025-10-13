<?php
/**
 * Register API Endpoint
 * Method: POST
 * Parameters: name, email, password, phone (optional)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

checkRequestMethod('POST');

$database = new Database();
$db = $database->getConnection();

$data = getJsonInput();

// Validate input
if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
    jsonResponse(false, 'Name, email and password are required', null, 400);
}

$name = sanitizeInput($data['name']);
$email = sanitizeInput($data['email']);
$password = $data['password'];
$phone = isset($data['phone']) ? sanitizeInput($data['phone']) : null;

// Validate email format
if (!isValidEmail($email)) {
    jsonResponse(false, 'Invalid email format', null, 400);
}

// Validate password length
if (strlen($password) < 6) {
    jsonResponse(false, 'Password must be at least 6 characters', null, 400);
}

try {
    // Check if email already exists
    $checkQuery = "SELECT id FROM users WHERE email = :email";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':email', $email);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        jsonResponse(false, 'Email already registered', null, 409);
    }
    
    // Generate unique barcode ID
    $barcodeId = generateBarcodeId('USER');
    
    // Check if barcode already exists (unlikely but safe)
    $barcodeCheckQuery = "SELECT id FROM users WHERE barcode_id = :barcode_id";
    $barcodeCheckStmt = $db->prepare($barcodeCheckQuery);
    $barcodeCheckStmt->bindParam(':barcode_id', $barcodeId);
    $barcodeCheckStmt->execute();
    
    while ($barcodeCheckStmt->rowCount() > 0) {
        $barcodeId = generateBarcodeId('USER');
        $barcodeCheckStmt->execute();
    }
    
    // Hash password
    $hashedPassword = hashPassword($password);
    
    // Insert new user
    $insertQuery = "INSERT INTO users (name, email, password, role, barcode_id, phone) 
                    VALUES (:name, :email, :password, 'attendee', :barcode_id, :phone)";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(':name', $name);
    $insertStmt->bindParam(':email', $email);
    $insertStmt->bindParam(':password', $hashedPassword);
    $insertStmt->bindParam(':barcode_id', $barcodeId);
    $insertStmt->bindParam(':phone', $phone);
    
    if ($insertStmt->execute()) {
        $userId = $db->lastInsertId();
        
        // Get the created user
        $getUserQuery = "SELECT id, name, email, role, barcode_id, phone, created_at FROM users WHERE id = :id";
        $getUserStmt = $db->prepare($getUserQuery);
        $getUserStmt->bindParam(':id', $userId);
        $getUserStmt->execute();
        $user = $getUserStmt->fetch();
        
        jsonResponse(true, 'Registration successful', ['user' => $user], 201);
    } else {
        jsonResponse(false, 'Registration failed', null, 500);
    }
    
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
?>
