<?php
/**
 * Authentication Middleware
 * Include this file to protect API endpoints with JWT authentication
 */

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/helpers.inc.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Require authentication - validates JWT token and sets user context
 */
function requireAuth() {
    try {
        $token = JWTManager::getTokenFromHeader();
        
        if (!$token) {
            sendError('Authentication required', 401);
        }
        
        $decoded = JWTManager::validateToken($token);
        
        // Check if token exists in sessions table - if it does, it must be valid (not blacklisted)
        // If token doesn't exist in sessions table, JWT expiration validation above is sufficient
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM `user_sessions` WHERE token = ?");
        $stmt->execute([$token]);
        $session = $stmt->fetch();
        
        // If session exists in database, check if it's been blacklisted (expires_at set to past on logout)
        if ($session) {
            $stmt = $db->prepare("SELECT id FROM `user_sessions` WHERE token = ? AND expires_at > NOW()");
            $stmt->execute([$token]);
            $validSession = $stmt->fetch();
            
            if (!$validSession) {
                sendError('Token has been revoked', 401);
            }
        }
        
        // Set global user context
        $GLOBALS['current_user_id'] = $decoded['user_id'];
        $GLOBALS['current_user_email'] = $decoded['email'];
        
        return $decoded;
    } catch (Exception $e) {
        sendError($e->getMessage(), 401);
    }
}

/**
 * Get current authenticated user ID
 */
function getCurrentUserId() {
    return $GLOBALS['current_user_id'] ?? null;
}

/**
 * Get current authenticated user email
 */
function getCurrentUserEmail() {
    return $GLOBALS['current_user_email'] ?? null;
}

/**
 * Check if user is authenticated (optional auth)
 */
function isAuthenticated() {
    try {
        $token = JWTManager::getTokenFromHeader();
        if (!$token) {
            return false;
        }
        $decoded = JWTManager::validateToken($token);
        $GLOBALS['current_user_id'] = $decoded['user_id'];
        $GLOBALS['current_user_email'] = $decoded['email'];
        return true;
    } catch (Exception $e) {
        return false;
    }
}

