<?php
/**
 * Application Configuration
 * Contains constants and settings for the Splitter application
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');

// Application constants
define('APP_NAME', 'Splitter');
define('APP_VERSION', '1.0.0');

// Base paths
define('BASE_PATH', dirname(__DIR__, 2));
define('BACKEND_PATH', BASE_PATH . '/backend');
define('FRONTEND_PATH', BASE_PATH . '/frontend');
define('UPLOAD_PATH', BACKEND_PATH . '/uploads');

// API configuration
define('API_BASE_URL', '/backend/api');
define('API_VERSION', 'v1');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('RECEIPTS_UPLOAD_DIR', UPLOAD_PATH . '/receipts');

// JWT settings (actual secret should be in environment variable)
define('JWT_SECRET', getenv('JWT_SECRET') ?: '8eab021e3ad7e21e53255d950643443a44b08f55496003d31a2cd2cf2817be99');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 86400); // 24 hours in seconds

// Database settings (override with environment variables in production)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'splitter_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'root1234');
define('DB_CHARSET', 'utf8mb4');

// CORS settings (adjust for production)
define('CORS_ALLOWED_ORIGINS', '*'); // Use specific domain in production
define('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization');

// Pagination defaults
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Response format
header('Content-Type: application/json; charset=utf-8');

// CORS headers (only for web requests, not CLI)
if (php_sapi_name() !== 'cli' && defined('CORS_ALLOWED_ORIGINS')) {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
    header('Access-Control-Allow-Origin: ' . CORS_ALLOWED_ORIGINS);
    header('Access-Control-Allow-Methods: ' . CORS_ALLOWED_METHODS);
    header('Access-Control-Allow-Headers: ' . CORS_ALLOWED_HEADERS);
    header('Access-Control-Allow-Credentials: true');
    
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

