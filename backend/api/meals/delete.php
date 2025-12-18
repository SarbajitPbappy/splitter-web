<?php
/**
 * Delete Meal Entry Endpoint
 * DELETE /backend/api/meals/delete.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Meal.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('DELETE');

requireAuth();
$userId = getCurrentUserId();

$data = getRequestBody();

// Validate input
$rules = [
    'meal_id' => ['type' => 'int', 'required' => true, 'min' => 1]
];

$validation = sanitizeInput($data, $rules);
if (!empty($validation['errors'])) {
    sendError(implode(', ', $validation['errors']), 400);
}

$mealId = $validation['data']['meal_id'];

try {
    $group = new Group();
    
    // Get meal details to verify ownership and check group status
    $db = getDB();
    $stmt = $db->prepare("
        SELECT m.*, g.is_closed
        FROM `meals` m
        INNER JOIN `groups` g ON m.group_id = g.group_id
        WHERE m.meal_id = ?
    ");
    $stmt->execute([$mealId]);
    $mealData = $stmt->fetch();
    
    if (!$mealData) {
        sendError('Meal not found', 404);
    }
    
    // Check if group is closed
    if ($mealData['is_closed']) {
        sendError('Cannot delete meals from a closed group', 403);
    }
    
    // Verify user is the one who added the meal (only allow deleting your own meals)
    if ($mealData['user_id'] != $userId) {
        sendError('You can only delete your own meal entries', 403);
    }
    
    // Delete meal
    $stmt = $db->prepare("DELETE FROM `meals` WHERE meal_id = ?");
    $stmt->execute([$mealId]);
    
    sendSuccess(null, 'Meal deleted successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'delete_meal']);
    sendError($e->getMessage(), 400);
}

