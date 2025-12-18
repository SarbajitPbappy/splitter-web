<?php
/**
 * Update User Profile Endpoint
 * PUT /backend/api/users/update_profile.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/User.php';

requireMethod('PUT');

requireAuth();
$userId = getCurrentUserId();

$data = getRequestBody();

// Validate input (all fields optional for update)
$rules = [
    'name' => ['type' => 'string', 'required' => false, 'min_length' => 2, 'max_length' => 255],
    'profile_picture' => ['type' => 'string', 'required' => false],
    'password' => ['type' => 'string', 'required' => false, 'min_length' => 8]
];

$validation = sanitizeInput($data, $rules);
if (!empty($validation['errors'])) {
    sendError(implode(', ', $validation['errors']), 400);
}

$sanitized = $validation['data'];

// Validate password if provided
if (isset($sanitized['password']) && !validatePassword($sanitized['password'])) {
    sendError('Password must be at least 8 characters with uppercase, lowercase, and number', 400);
}

try {
    $user = new User();
    $updatedUser = $user->updateProfile($userId, $sanitized);
    
    // Remove sensitive data
    unset($updatedUser['password_hash']);
    
    sendSuccess($updatedUser, 'Profile updated successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'update_profile']);
    sendError($e->getMessage(), 400);
}

