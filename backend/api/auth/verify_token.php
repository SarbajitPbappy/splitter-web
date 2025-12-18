<?php
/**
 * Token Verification Endpoint
 * GET /backend/api/auth/verify_token.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../classes/User.php';

requireMethod('GET');

try {
    $decoded = requireAuth();
    
    // Get user data
    $user = new User();
    $userData = $user->getById($decoded['user_id']);
    
    if (!$userData) {
        sendError('User not found', 404);
    }
    
    // Remove sensitive data
    unset($userData['password_hash']);
    
    sendSuccess([
        'user' => $userData,
        'token_valid' => true
    ], 'Token is valid');
} catch (Exception $e) {
    sendError($e->getMessage(), 401);
}

