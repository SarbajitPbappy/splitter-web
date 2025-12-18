<?php
require_once __DIR__ . '/backend/config/database.php';

try {
    $db = getDB();
    echo "Database connection successful!\n";
    echo "Connected to database: " . DB_NAME . "\n";
    
    // Test query
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users table exists with " . $result['count'] . " rows\n";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}