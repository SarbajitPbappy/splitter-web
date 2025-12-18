<?php
/**
 * Delete Expense Endpoint
 * DELETE /backend/api/expenses/delete.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Expense.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('DELETE');

requireAuth();
$userId = getCurrentUserId();

$data = getRequestBody();
$expenseId = isset($data['id']) ? (int) $data['id'] : 0;

if ($expenseId <= 0) {
    sendError('Invalid expense ID', 400);
}

try {
    // Get expense first to check group
    $expense = new Expense();
    $expenseData = $expense->getById($expenseId);
    
    if (!$expenseData) {
        sendError('Expense not found', 404);
    }
    
    // Check if group is closed
    $group = new Group();
    if ($group->isClosed($expenseData['group_id'])) {
        sendError('Cannot delete expenses from a closed group', 403);
    }
    
    $expense->delete($expenseId, $userId);
    
    sendSuccess(null, 'Expense deleted successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'delete_expense']);
    sendError($e->getMessage(), 400);
}

