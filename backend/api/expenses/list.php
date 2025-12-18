<?php
/**
 * List Expenses for Group Endpoint
 * GET /backend/api/expenses/list.php?group_id={id}&page={page}&page_size={size}
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Expense.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('GET');

requireAuth();
$userId = getCurrentUserId();

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$pageSize = isset($_GET['page_size']) ? (int) $_GET['page_size'] : DEFAULT_PAGE_SIZE;

if ($groupId <= 0) {
    sendError('Invalid group ID', 400);
}

// Limit page size
if ($pageSize > MAX_PAGE_SIZE) {
    $pageSize = MAX_PAGE_SIZE;
}
if ($pageSize < 1) {
    $pageSize = DEFAULT_PAGE_SIZE;
}

try {
    $group = new Group();
    
    // Verify user is member
    if (!$group->isMember($groupId, $userId)) {
        sendError('Access denied', 403);
    }
    
    $expense = new Expense();
    $result = $expense->getGroupExpenses($groupId, $page, $pageSize);
    
    sendSuccess($result, 'Expenses retrieved successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'list_expenses']);
    sendError('Failed to retrieve expenses', 500);
}

