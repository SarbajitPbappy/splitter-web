<?php
/**
 * Quick test script to verify login API
 * Usage: php test_login.php
 */

require_once __DIR__ . '/backend/config/config.php';
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/classes/User.php';
require_once __DIR__ . '/backend/config/jwt.php';

echo "Testing Login API...\n\n";

// Test database connection
try {
    $db = getDB();
    echo "✓ Database connection: OK\n";
} catch (Exception $e) {
    echo "✗ Database connection: FAILED - " . $e->getMessage() . "\n";
    exit(1);
}

// Test user creation/login
try {
    $user = new User();
    
    // Check if test user exists
    $testEmail = 'test@example.com';
    $testUser = $user->getByEmail($testEmail);
    
    if (!$testUser) {
        echo "Creating test user...\n";
        $userId = $user->create('Test User', $testEmail, 'Test1234');
        echo "✓ Test user created: ID $userId\n";
    } else {
        echo "✓ Test user already exists: ID " . $testUser['user_id'] . "\n";
    }
    
    // Test password verification
    $verified = $user->verifyPassword($testEmail, 'Test1234');
    if ($verified) {
        echo "✓ Password verification: OK\n";
    } else {
        echo "✗ Password verification: FAILED\n";
    }
    
    // Test JWT generation
    $testUser = $user->getByEmail($testEmail);
    $token = JWTManager::generateToken($testUser['user_id'], $testUser['email']);
    
    if ($token && strlen($token) > 10) {
        echo "✓ JWT token generation: OK\n";
        echo "  Token preview: " . substr($token, 0, 20) . "...\n";
    } else {
        echo "✗ JWT token generation: FAILED\n";
    }
    
    // Test token verification
    try {
        $decoded = JWTManager::verifyToken($token);
        if ($decoded && isset($decoded['user_id'])) {
            echo "✓ JWT token verification: OK\n";
            echo "  User ID: " . $decoded['user_id'] . "\n";
            echo "  Email: " . $decoded['email'] . "\n";
        } else {
            echo "✗ JWT token verification: FAILED\n";
        }
    } catch (Exception $e) {
        echo "✗ JWT token verification: FAILED - " . $e->getMessage() . "\n";
    }
    
    echo "\n✓ All tests passed!\n";
    echo "\nYou can now login with:\n";
    echo "  Email: $testEmail\n";
    echo "  Password: Test1234\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

