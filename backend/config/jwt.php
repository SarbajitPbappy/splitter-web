<?php
/**
 * JWT Token Management
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JWTManager {
    
    /**
     * Generate JWT token for user
     */
    public static function generateToken($userId, $email) {
        $issuedAt = time();
        $expiration = $issuedAt + JWT_EXPIRATION;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expiration,
            'user_id' => $userId,
            'email' => $email
        ];
        
        return JWT::encode($payload, JWT_SECRET, JWT_ALGORITHM);
    }
    
    /**
     * Verify and decode JWT token
     */
    public static function verifyToken($token) {
        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new Exception('Token has expired');
        } catch (SignatureInvalidException $e) {
            throw new Exception('Invalid token signature');
        } catch (Exception $e) {
            throw new Exception('Invalid token: ' . $e->getMessage());
        }
    }
    
    /**
     * Extract token from Authorization header
     */
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization']) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    /**
     * Validate token and return user data
     */
    public static function validateToken($token = null) {
        if ($token === null) {
            $token = self::getTokenFromHeader();
        }
        
        if (!$token) {
            throw new Exception('Token not provided');
        }
        
        return self::verifyToken($token);
    }
}

