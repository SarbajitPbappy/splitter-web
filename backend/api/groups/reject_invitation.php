<?php
/**
 * Reject Group Invitation Endpoint
 * POST /backend/api/groups/reject_invitation.php
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
    $group->rejectInvitation($sanitized['token'], $userId);
    
    sendSuccess(null, 'Invitation rejected successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'reject_invitation']);
    sendError($e->getMessage(), 400);
}

