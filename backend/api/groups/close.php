<?php
/**
 * Close Group Endpoint
 * PUT /backend/api/groups/close.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('PUT');

requireAuth();
$userId = getCurrentUserId();

$data = getRequestBody();

// Validate input
$rules = [
    'group_id' => ['type' => 'int', 'required' => true, 'min' => 1]
];

$validation = sanitizeInput($data, $rules);
if (!empty($validation['errors'])) {
    sendError(implode(', ', $validation['errors']), 400);
}

$sanitized = $validation['data'];
$groupId = $sanitized['group_id'];

try {
    $group = new Group();
    $group->close($groupId, $userId);
    
    $groupData = $group->getById($groupId);
    sendSuccess($groupData, 'Group closed successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'close_group']);
    sendError($e->getMessage(), 400);
}

