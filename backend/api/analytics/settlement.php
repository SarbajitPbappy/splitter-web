<?php
/**
 * Settlement Calculations Endpoint
 * GET /backend/api/analytics/settlement.php?group_id={id}
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Settlement.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('GET');

requireAuth();
$userId = getCurrentUserId();

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;

if ($groupId <= 0) {
    sendError('Invalid group ID', 400);
}

try {
    $group = new Group();
    
    // Verify user is member
    if (!$group->isMember($groupId, $userId)) {
        sendError('Access denied', 403);
    }
    
    $settlement = new Settlement();
    $result = $settlement->calculateSettlements($groupId);
    
    sendSuccess($result, 'Settlement calculations retrieved successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'settlement']);
    sendError('Failed to calculate settlements', 500);
}

