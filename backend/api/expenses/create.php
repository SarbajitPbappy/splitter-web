<?php
/**
 * Create Expense Endpoint
 * POST /backend/api/expenses/create.php
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.inc.php';
require_once __DIR__ . '/../../includes/helpers.inc.php';
require_once __DIR__ . '/../../includes/validation.inc.php';
require_once __DIR__ . '/../../classes/Expense.php';
require_once __DIR__ . '/../../classes/Group.php';

requireMethod('POST');

requireAuth();
$userId = getCurrentUserId();

$data = getRequestBody();

// Extract splits before validation (since it's an array, not a string)
$splits = [];
if (isset($data['splits']) && is_array($data['splits'])) {
    $splits = $data['splits'];
}

// Validate input (exclude splits from validation since it's an array)
$rules = [
    'group_id' => ['type' => 'int', 'required' => true, 'min' => 1],
    'paid_by_user_id' => ['type' => 'int', 'required' => true, 'min' => 1],
    'amount' => ['type' => 'float', 'required' => true, 'min' => 0.01],
    'description' => ['type' => 'string', 'required' => false, 'max_length' => 500],
    'split_type' => ['type' => 'string', 'required' => true, 'validate' => function($val) {
        return in_array($val, ['Equal', 'Unequal', 'Shares']) ? true : 'Invalid split type';
    }],
    'expense_date' => ['type' => 'string', 'required' => true]
];

$validation = sanitizeInput($data, $rules);
if (!empty($validation['errors'])) {
    sendError(implode(', ', $validation['errors']), 400);
}

$sanitized = $validation['data'];

// Validate date
$expenseDate = $sanitized['expense_date'];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expenseDate)) {
    sendError('Invalid date format. Use YYYY-MM-DD', 400);
}

// Handle receipt upload if provided
$receiptImage = null;
if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
    require_once __DIR__ . '/../../includes/validation.inc.php';
    $validation = validateFileUpload($_FILES['receipt'], ALLOWED_IMAGE_TYPES, MAX_FILE_SIZE);
    if (!$validation['valid']) {
        sendError(implode(', ', $validation['errors']), 400);
    }
    
    // Generate unique filename
    $extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('receipt_', true) . '.' . $extension;
    $uploadPath = RECEIPTS_UPLOAD_DIR . '/' . $filename;
    
    // Ensure directory exists
    ensureUploadDirectory(RECEIPTS_UPLOAD_DIR);
    
    if (move_uploaded_file($_FILES['receipt']['tmp_name'], $uploadPath)) {
        $receiptImage = 'receipts/' . $filename;
    }
}

// Splits already extracted above, validate structure if needed
if (!empty($splits) && !is_array($splits)) {
    sendError('Invalid splits format', 400);
}

try {
    // Check if group is closed
    $group = new Group();
    if (!$group->isMember($sanitized['group_id'], $userId)) {
        sendError('Access denied', 403);
    }
    
    if ($group->isClosed($sanitized['group_id'])) {
        sendError('Cannot add expenses to a closed group', 403);
    }
    
    $expense = new Expense();
    $expenseId = $expense->create(
        $sanitized['group_id'],
        $sanitized['paid_by_user_id'],
        $sanitized['amount'],
        $sanitized['description'] ?? '',
        $sanitized['split_type'],
        $expenseDate,
        $splits,
        $receiptImage
    );
    
    $expenseData = $expense->getById($expenseId);
    sendSuccess($expenseData, 'Expense created successfully');
} catch (Exception $e) {
    logError($e->getMessage(), ['endpoint' => 'create_expense']);
    sendError($e->getMessage(), 400);
}

