<?php
/**
 * Get User Profile Endpoint
 * GET /backend/api/users/get_profile.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../classes/User.php';

requireMethod('GET');

requireAuth();
$userId = getCurrentUserId();

try {
    $user = new User();
    $userData = $user->getById($userId);
    
    if (!$userData) {
        sendError('User not found', 404);
    }
    
    // Remove sensitive data
    unset($userData['password_hash']);
    
    sendSuccess($userData, 'Profile retrieved successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'get_profile']);
    sendError('Failed to retrieve profile', 500);
}

