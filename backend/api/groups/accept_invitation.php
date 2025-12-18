<?php
/**
 * Accept Group Invitation Endpoint
 * POST /backend/api/groups/accept_invitation.php
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
    'token' => ['type' => 'string', 'required' => true]
];

$validation = sanitizeInput($data, $rules);
if (!empty($validation['errors'])) {
    sendError(implode(', ', $validation['errors']), 400);
}

$sanitized = $validation['data'];

try {
    $group = new Group();
    $groupId = $group->acceptInvitation($sanitized['token'], $userId);
    
    $groupData = $group->getById($groupId);
    sendSuccess($groupData, 'Invitation accepted successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'accept_invitation']);
    sendError($e->getMessage(), 400);
}

