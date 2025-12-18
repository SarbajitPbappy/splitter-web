<?php
/**
 * Get Expense Details Endpoint
 * GET /backend/api/expenses/get.php?id={expense_id}
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

$expenseId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($expenseId <= 0) {
    sendError('Invalid expense ID', 400);
}

try {
    $expense = new Expense();
    $expenseData = $expense->getById($expenseId);
    
    if (!$expenseData) {
        sendError('Expense not found', 404);
    }
    
    // Verify user is member of group
    $group = new Group();
    if (!$group->isMember($expenseData['group_id'], $userId)) {
        sendError('Access denied', 403);
    }
    
    sendSuccess($expenseData, 'Expense retrieved successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'get_expense']);
    sendError('Failed to retrieve expense', 500);
}

