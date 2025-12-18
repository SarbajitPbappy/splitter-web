<?php
/**
 * Create Group Endpoint
 * POST /backend/api/groups/create.php
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
    'name' => ['type' => 'string', 'required' => true, 'min_length' => 1, 'max_length' => 255],
    'description' => ['type' => 'string', 'required' => false, 'max_length' => 1000],
    'type' => ['type' => 'string', 'required' => true, 'validate' => function($val) {
        return in_array($val, ['Trip', 'Bachelor Mess']) ? true : 'Type must be Trip or Bachelor Mess';
    }]
];

$validation = sanitizeInput($data, $rules);
if (!empty($validation['errors'])) {
    sendError(implode(', ', $validation['errors']), 400);
}

$sanitized = $validation['data'];

try {
    $group = new Group();
    $groupId = $group->create(
        $sanitized['name'],
        $sanitized['description'] ?? '',
        $sanitized['type'],
        $userId
    );
    
    $groupData = $group->getById($groupId);
    sendSuccess($groupData, 'Group created successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'create_group']);
    sendError($e->getMessage(), 400);
}

