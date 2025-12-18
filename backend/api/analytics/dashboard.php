<?php
/**
 * Group Analytics Dashboard Endpoint
 * GET /backend/api/analytics/dashboard.php?group_id={id}
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Group.php';
require_once __DIR__ . '/../../classes/Expense.php';
require_once __DIR__ . '/../../config/database.php';

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
    
    $db = getDB();
    $analytics = [];
    
    // Total expenses
    $stmt = $db->prepare("
        SELECT COUNT(*) as count, SUM(amount) as total
        FROM `expenses`
        WHERE group_id = ?
    ");
    $stmt->execute([$groupId]);
    $total = $stmt->fetch();
    $analytics['total_expenses'] = (int) $total['count'];
    $analytics['total_amount'] = (float) ($total['total'] ?? 0);
    
    // Expenses by member (who paid)
    $stmt = $db->prepare("
        SELECT u.user_id, u.name, SUM(e.amount) as total_paid, COUNT(*) as expense_count
        FROM `expenses` e
        INNER JOIN `users` u ON e.paid_by_user_id = u.user_id
        WHERE e.group_id = ?
        GROUP BY u.user_id, u.name
        ORDER BY total_paid DESC
    ");
    $stmt->execute([$groupId]);
    $analytics['by_member'] = $stmt->fetchAll();
    
    // Expenses by month (last 6 months)
    $stmt = $db->prepare("
        SELECT DATE_FORMAT(expense_date, '%Y-%m') as month, SUM(amount) as total
        FROM `expenses`
        WHERE group_id = ? AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt->execute([$groupId]);
    $analytics['by_month'] = $stmt->fetchAll();
    
    // Expenses by split type
    $stmt = $db->prepare("
        SELECT split_type, COUNT(*) as count, SUM(amount) as total
        FROM `expenses`
        WHERE group_id = ?
        GROUP BY split_type
    ");
    $stmt->execute([$groupId]);
    $analytics['by_split_type'] = $stmt->fetchAll();
    
    sendSuccess($analytics, 'Analytics retrieved successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'dashboard_analytics']);
    sendError('Failed to retrieve analytics', 500);
}

