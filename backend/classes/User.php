<?php
/**
 * User Class
 * Handles user-related operations
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Create new user
     */
    public function create($name, $email, $password, $googleId = null) {
        // Check if email already exists
        if ($this->emailExists($email)) {
            throw new Exception('Email already registered');
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("
            INSERT INTO `users` (name, email, password_hash, google_id)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$name, $email, $passwordHash, $googleId]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Get user by ID
     */
    public function getById($userId) {
        $stmt = $this->db->prepare("
            SELECT user_id, email, name, profile_picture, google_id, created_at, updated_at
            FROM `users`
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $stmt = $this->db->prepare("
            SELECT user_id, email, name, password_hash, profile_picture, google_id, created_at, updated_at
            FROM `users`
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    /**
     * Get user by Google ID
     */
    public function getByGoogleId($googleId) {
        $stmt = $this->db->prepare("
            SELECT user_id, email, name, profile_picture, google_id, created_at, updated_at
            FROM `users`
            WHERE google_id = ?
        ");
        $stmt->execute([$googleId]);
        return $stmt->fetch();
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($email, $password) {
        $user = $this->getByEmail($email);
        if (!$user) {
            return false;
        }
        
        return password_verify($password, $user['password_hash']);
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $data) {
        $updates = [];
        $params = [];
        
        if (isset($data['name'])) {
            $updates[] = "name = ?";
            $params[] = $data['name'];
        }
        
        if (isset($data['profile_picture'])) {
            $updates[] = "profile_picture = ?";
            $params[] = $data['profile_picture'];
        }
        
        if (isset($data['password'])) {
            $updates[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updates)) {
            throw new Exception('No fields to update');
        }
        
        $params[] = $userId;
        
        $stmt = $this->db->prepare("
            UPDATE `users`
            SET " . implode(', ', $updates) . "
            WHERE user_id = ?
        ");
        
        $stmt->execute($params);
        
        return $this->getById($userId);
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM `users` WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
}

