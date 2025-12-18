<?php
/**
 * User Logout Endpoint
 * POST /backend/api/auth/logout.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../config/database.php';

requireMethod('POST');

requireAuth();
$userId = getCurrentUserId();

try {
    $token = JWTManager::getTokenFromHeader();
    
    if ($token) {
        // Blacklist token by updating expires_at to past
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE `user_sessions`
            SET expires_at = NOW() - INTERVAL 1 DAY
            WHERE user_id = ? AND token = ?
        ");
        $stmt->execute([$userId, $token]);
    }
    
    sendSuccess(null, 'Logged out successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'logout']);
    sendError('Logout failed', 500);
}

