<?php
/**
 * Record Market Expense Endpoint
 * POST /backend/api/meals/market_expense.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Meal.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('POST');

requireAuth();
$userId = getCurrentUserId();

$data = getRequestBody();

// Validate input
$rules = [
    'group_id' => ['type' => 'int', 'required' => true, 'min' => 1],
    'month_year' => ['type' => 'string', 'required' => true],
    'total_amount' => ['type' => 'float', 'required' => true, 'min' => 0.01]
];

$validation = sanitizeInput($data, $rules);
if (!empty($validation['errors'])) {
    sendError(implode(', ', $validation['errors']), 400);
}

$sanitized = $validation['data'];

// Validate month format (YYYY-MM)
if (!preg_match('/^\d{4}-\d{2}$/', $sanitized['month_year'])) {
    sendError('Invalid month format. Use YYYY-MM', 400);
}

try {
    $group = new Group();
    
    // Verify user is member
    if (!$group->isMember($sanitized['group_id'], $userId)) {
        sendError('Access denied', 403);
    }
    
    // Check if group is closed
    if ($group->isClosed($sanitized['group_id'])) {
        sendError('Cannot record market expenses for a closed group', 403);
    }
    
    $meal = new Meal();
    $meal->recordMarketExpense(
        $sanitized['group_id'],
        $sanitized['month_year'],
        $sanitized['total_amount'],
        $userId
    );
    
    sendSuccess(['month_year' => $sanitized['month_year']], 'Market expense recorded successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'record_market_expense']);
    sendError($e->getMessage(), 400);
}

