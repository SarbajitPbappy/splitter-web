<?php
/**
 * Invite User to Group Endpoint
 * POST /backend/api/groups/invite.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('POST');

requireAuth();
$userId = getCurrentUserId();

$data = getRequestBody();

// Validate input
$rules = [
    'group_id' => ['type' => 'int', 'required' => true, 'min' => 1],
    'email' => ['type' => 'email', 'required' => true]
];

$validation = sanitizeInput($data, $rules);
if (!empty($validation['errors'])) {
    sendError(implode(', ', $validation['errors']), 400);
}

$sanitized = $validation['data'];
$groupId = $sanitized['group_id'];

try {
    $group = new Group();
    
    // Verify user is member
    if (!$group->isMember($groupId, $userId)) {
        sendError('Access denied', 403);
    }
    
    $token = $group->createInvitation($groupId, $sanitized['email'], $userId);
    
    sendSuccess([
        'token' => $token,
        'email' => $sanitized['email']
    ], 'Invitation created successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'invite_user']);
    sendError($e->getMessage(), 400);
}

