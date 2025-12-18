<?php
/**
 * Report Data Endpoint
 * GET /backend/api/analytics/reports.php?group_id={id}
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Group.php';
require_once __DIR__ . '/../../classes/Expense.php';
require_once __DIR__ . '/../../classes/Settlement.php';

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
    
    $groupData = $group->getById($groupId);
    $members = $group->getMembers($groupId);
    
    $expense = new Expense();
    $expensesData = $expense->getGroupExpenses($groupId, 1, 1000);
    
    $settlement = new Settlement();
    $settlementData = $settlement->calculateSettlements($groupId);
    
    $report = [
        'group' => $groupData,
        'members' => $members,
        'expenses' => $expensesData['expenses'],
        'settlement' => $settlementData
    ];
    
    sendSuccess($report, 'Report data retrieved successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'reports']);
    sendError('Failed to retrieve report data', 500);
}

