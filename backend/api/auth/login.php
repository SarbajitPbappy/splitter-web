<?php
/**
 * User Login Endpoint
 * POST /backend/api/auth/login.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/jwt.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../config/database.php';

requireMethod('POST');

$data = getRequestBody();

// Validate input
$rules = [
    'email' => ['type' => 'email', 'required' => true],
    'password' => ['type' => 'string', 'required' => true]
];

$validation = sanitizeInput($data, $rules);
if (!empty($validation['errors'])) {
    sendError(implode(', ', $validation['errors']), 400);
}

$sanitized = $validation['data'];

try {
    $user = new User();
    
    // Verify credentials
    if (!$user->verifyPassword($sanitized['email'], $sanitized['password'])) {
        sendError('Invalid email or password', 401);
    }
    
    // Get user data
    $userData = $user->getByEmail($sanitized['email']);
    
    // Generate JWT token
    $token = JWTManager::generateToken($userData['user_id'], $userData['email']);
    
    // Store token in sessions table
    $db = getDB();
    $expiresAt = date('Y-m-d H:i:s', time() + JWT_EXPIRATION);
    $stmt = $db->prepare("
        INSERT INTO `user_sessions` (user_id, token, expires_at)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$userData['user_id'], $token, $expiresAt]);
    
    // Remove password from response
    unset($userData['password_hash']);
    
    sendSuccess([
        'user' => $userData,
        'token' => $token
    ], 'Login successful');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'login', 'email' => $sanitized['email']]);
    sendError('Login failed', 500);
}

