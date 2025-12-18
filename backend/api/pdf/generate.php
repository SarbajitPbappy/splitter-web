<?php
/**
 * Generate PDF Report Endpoint
 * GET /backend/api/pdf/generate.php?group_id={id}
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/PDFGenerator.php';
require_once __DIR__ . '/../../classes/Group.php';

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
    
    $pdfGenerator = new PDFGenerator();
    $pdfContent = $pdfGenerator->generateReport($groupId);
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="group_report_' . $groupId . '_' . date('Y-m-d') . '.pdf"');
    header('Content-Length: ' . strlen($pdfContent));
    
    echo $pdfContent;
    exit();
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'generate_pdf']);
    sendError('Failed to generate PDF', 500);
}

