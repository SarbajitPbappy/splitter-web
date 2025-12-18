<?php
/**
 * Get Group Details Endpoint
 * GET /backend/api/groups/get.php?id={group_id}
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('GET');

requireAuth();
$userId = getCurrentUserId();

$groupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($groupId <= 0) {
    sendError('Invalid group ID', 400);
}

try {
    $group = new Group();
    
    // Verify user is member
    if (!$group->isMember($groupId, $userId)) {
        sendError('Access denied', 403);
    }
    
    $groupData = $group->getById($groupId);
    if (!$groupData) {
        sendError('Group not found', 404);
    }
    
    // Get members
    $groupData['members'] = $group->getMembers($groupId);
    
    sendSuccess($groupData, 'Group retrieved successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'get_group']);
    sendError('Failed to retrieve group', 500);
}

