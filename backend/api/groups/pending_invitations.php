<?php
/**
 * Get Pending Invitations Endpoint
 * GET /backend/api/groups/pending_invitations.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('GET');

requireAuth();
$userId = getCurrentUserId();

try {
    $group = new Group();
    $invitations = $group->getPendingInvitationsByUserId($userId);
    
    sendSuccess($invitations, 'Pending invitations retrieved successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'pending_invitations']);
    sendError('Failed to retrieve invitations', 500);
}

