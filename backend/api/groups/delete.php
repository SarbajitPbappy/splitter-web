<?php
/**
 * Delete Group Endpoint
 * DELETE /backend/api/groups/delete.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('DELETE');

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
    $group->delete($groupId, $userId);
    
    sendSuccess(null, 'Group deleted successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'delete_group']);
    sendError($e->getMessage(), 400);
}

