<?php
/**
 * Get Invitation Details Endpoint
 * GET /backend/api/groups/get_invitation.php?token={token}
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('GET');

$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    sendError('Token is required', 400);
}

try {
    $group = new Group();
    $invitation = $group->getInvitationByToken($token);
    
    if (!$invitation) {
        sendError('Invalid or expired invitation', 404);
    }
    
    sendSuccess($invitation, 'Invitation retrieved successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'get_invitation']);
    sendError('Failed to retrieve invitation', 500);
}

