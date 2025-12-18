<?php
/**
 * Helper Functions
 */

/**
 * Send JSON response
 */
function sendResponse($success, $data = null, $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    
    $response = [
        'success' => $success
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    if ($message !== '') {
        $response['message'] = $message;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Send success response
 */
function sendSuccess($data = null, $message = 'Success') {
    sendResponse(true, $data, $message, 200);
}

/**
 * Send error response
 */
function sendError($message = 'An error occurred', $statusCode = 400, $data = null) {
    sendResponse(false, $data, $message, $statusCode);
}

/**
 * Get request body as JSON
 */
function getRequestBody() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError('Invalid JSON in request body', 400);
    }
    
    return $data ?: [];
}

/**
 * Get request method
 */
function getRequestMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Check if request method matches
 */
function requireMethod($method) {
    if (getRequestMethod() !== strtoupper($method)) {
        sendError('Method not allowed', 405);
    }
}

/**
 * Log error
 */
function logError($message, $context = []) {
    $logMessage = date('Y-m-d H:i:s') . " - ERROR: $message";
    if (!empty($context)) {
        $logMessage .= " | Context: " . json_encode($context);
    }
    error_log($logMessage);
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y-m-d') {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    return $date->format($format);
}

/**
 * Format currency (BDT - Bangladeshi Taka)
 */
function formatCurrency($amount, $currency = 'BDT') {
    return '৳' . number_format((float)$amount, 2, '.', ',');
}

/**
 * Generate unique token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Create upload directory if it doesn't exist
 */
function ensureUploadDirectory($dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return is_dir($dir);
}

