<?php
/**
 * List User Groups Endpoint
 * GET /backend/api/groups/list.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('GET');

requireAuth();
$userId = getCurrentUserId();

try {
    $group = new Group();
    $groups = $group->getUserGroups($userId);
    
    sendSuccess($groups, 'Groups retrieved successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'list_groups']);
    sendError('Failed to retrieve groups', 500);
}

