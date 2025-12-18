<?php
/**
 * Add Meal Entry Endpoint
 * POST /backend/api/meals/add.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Meal.php';

requireMethod('POST');

requireAuth();
$userId = getCurrentUserId();

$data = getRequestBody();

// Validate input
$rules = [
    'group_id' => ['type' => 'int', 'required' => true, 'min' => 1],
    'meal_date' => ['type' => 'string', 'required' => true],
    'meal_type' => ['type' => 'string', 'required' => true, 'validate' => function($val) {
        return in_array($val, ['Breakfast', 'Lunch', 'Dinner']) ? true : 'Invalid meal type';
    }],
    'meal_category' => ['type' => 'string', 'required' => true, 'validate' => function($val) {
        return in_array($val, ['Mess Meal', 'Outside Meal']) ? true : 'Invalid meal category';
    }]
];

$validation = sanitizeInput($data, $rules);
if (!empty($validation['errors'])) {
    sendError(implode(', ', $validation['errors']), 400);
}

$sanitized = $validation['data'];

// Validate date
$mealDate = $sanitized['meal_date'];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $mealDate)) {
    sendError('Invalid date format. Use YYYY-MM-DD', 400);
}

try {
    // Check if group is closed
    require_once __DIR__ . '/../../classes/Group.php';
    $group = new Group();
    if (!$group->isMember($sanitized['group_id'], $userId)) {
        sendError('Access denied', 403);
    }
    
    if ($group->isClosed($sanitized['group_id'])) {
        sendError('Cannot add meals to a closed group', 403);
    }
    
    $meal = new Meal();
    $mealId = $meal->add(
        $sanitized['group_id'],
        $userId,
        $mealDate,
        $sanitized['meal_type'],
        $sanitized['meal_category']
    );
    
    sendSuccess(['meal_id' => $mealId], 'Meal added successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'add_meal']);
    sendError($e->getMessage(), 400);
}

