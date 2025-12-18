<?php
/**
 * User Registration Endpoint
 * POST /backend/api/auth/register.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/User.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('POST');

$data = getRequestBody();

// Validate input
$rules = [
    'name' => ['type' => 'string', 'required' => true, 'min_length' => 2, 'max_length' => 255],
    'email' => ['type' => 'email', 'required' => true],
    'password' => ['type' => 'string', 'required' => true, 'min_length' => 8],
    'invite_token' => ['type' => 'string', 'required' => false]
];

$validation = sanitizeInput($data, $rules);
if (!empty($validation['errors'])) {
    sendError(implode(', ', $validation['errors']), 400);
}

$sanitized = $validation['data'];

// Validate password strength
if (!validatePassword($sanitized['password'])) {
    sendError('Password must be at least 8 characters with uppercase, lowercase, and number', 400);
}

try {
    $user = new User();
    $userId = $user->create($sanitized['name'], $sanitized['email'], $sanitized['password']);
    
    // Get created user (without password)
    $createdUser = $user->getById($userId);
    unset($createdUser['password_hash']);
    
    $responseData = $createdUser;
    
    // If invite token is provided, try to accept the invitation
    if (!empty($sanitized['invite_token'])) {
        try {
            $group = new Group();
            $groupId = $group->acceptInvitation($sanitized['invite_token'], $userId);
            $responseData['group_id'] = $groupId;
        } catch (Exception $e) {
            // Log but don't fail registration if invitation acceptance fails
            logError('Failed to accept invitation during registration: ' . $e->getMessage(), [
                'endpoint' => 'register',
                'email' => $sanitized['email'],
                'invite_token' => $sanitized['invite_token']
            ]);
        }
    }
    
    sendSuccess($responseData, 'User registered successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'register', 'email' => $sanitized['email']]);
    sendError($e->getMessage(), 400);
}

