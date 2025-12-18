<?php
/**
 * List Meals for Group and Month Endpoint
 * GET /backend/api/meals/list.php?group_id={id}&month={YYYY-MM}
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Meal.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('GET');

requireAuth();
$userId = getCurrentUserId();

$groupId = isset($_GET['group_id']) ? (int) $_GET['group_id'] : 0;
$monthYear = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

if ($groupId <= 0) {
    sendError('Invalid group ID', 400);
}

// Validate month format
if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    sendError('Invalid month format. Use YYYY-MM', 400);
}

try {
    $group = new Group();
    
    // Verify user is member
    if (!$group->isMember($groupId, $userId)) {
        sendError('Access denied', 403);
    }
    
    $meal = new Meal();
    $meals = $meal->getMeals($groupId, $monthYear);
    
    sendSuccess($meals, 'Meals retrieved successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'list_meals']);
    sendError('Failed to retrieve meals', 500);
}

