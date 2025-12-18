<?php
/**
 * Group Class
 * Handles group-related operations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Email.php';
require_once __DIR__ . '/User.php';

class Group {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Create new group
     */
    public function create($name, $description, $type, $creatorId) {
        // Validate type
        if (!in_array($type, ['Trip', 'Bachelor Mess'])) {
            throw new Exception('Invalid group type');
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO `groups` (name, description, type, creator_id)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$name, $description, $type, $creatorId]);
        $groupId = $this->db->lastInsertId();
        
        // Add creator as member
        $this->addMember($groupId, $creatorId);
        
        return $groupId;
    }
    
    /**
     * Get group by ID
     */
    public function getById($groupId) {
        $stmt = $this->db->prepare("
            SELECT g.*, 
                   u.name as creator_name, 
                   u.email as creator_email,
                   COALESCE(expense_totals.total_amount, 0) as total_amount
            FROM `groups` g
            LEFT JOIN `users` u ON g.creator_id = u.user_id
            LEFT JOIN (
                SELECT group_id, SUM(amount) as total_amount
                FROM `expenses`
                GROUP BY group_id
            ) expense_totals ON g.group_id = expense_totals.group_id
            WHERE g.group_id = ?
        ");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();
        
        // Convert total_amount to float
        if ($group) {
            $group['total_amount'] = (float) $group['total_amount'];
        }
        
        return $group;
    }
    
    /**
     * Get groups for user
     */
    public function getUserGroups($userId) {
        $stmt = $this->db->prepare("
            SELECT g.*, 
                   u.name as creator_name,
                   COALESCE(expense_totals.total_amount, 0) as total_amount
            FROM `groups` g
            INNER JOIN `group_members` gm ON g.group_id = gm.group_id
            LEFT JOIN `users` u ON g.creator_id = u.user_id
            LEFT JOIN (
                SELECT group_id, SUM(amount) as total_amount
                FROM `expenses`
                GROUP BY group_id
            ) expense_totals ON g.group_id = expense_totals.group_id
            WHERE gm.user_id = ?
            ORDER BY g.updated_at DESC
        ");
        $stmt->execute([$userId]);
        $groups = $stmt->fetchAll();
        
        // Convert total_amount to float for each group
        foreach ($groups as &$group) {
            $group['total_amount'] = (float) $group['total_amount'];
        }
        
        return $groups;
    }
    
    /**
     * Add member to group
     */
    public function addMember($groupId, $userId) {
        // Check if already a member
        $stmt = $this->db->prepare("
            SELECT id FROM `group_members` WHERE group_id = ? AND user_id = ?
        ");
        $stmt->execute([$groupId, $userId]);
        if ($stmt->fetch()) {
            return true; // Already a member
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO `group_members` (group_id, user_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$groupId, $userId]);
        
        return true;
    }
    
    /**
     * Remove member from group
     */
    public function removeMember($groupId, $userId) {
        // Don't allow removing creator
        $group = $this->getById($groupId);
        if ($group['creator_id'] == $userId) {
            throw new Exception('Cannot remove group creator');
        }
        
        $stmt = $this->db->prepare("
            DELETE FROM `group_members`
            WHERE group_id = ? AND user_id = ?
        ");
        $stmt->execute([$groupId, $userId]);
        
        return true;
    }
    
    /**
     * Get group members
     */
    public function getMembers($groupId) {
        $stmt = $this->db->prepare("
            SELECT u.user_id, u.name, u.email, u.profile_picture, gm.joined_at
            FROM `group_members` gm
            INNER JOIN `users` u ON gm.user_id = u.user_id
            WHERE gm.group_id = ?
            ORDER BY gm.joined_at ASC
        ");
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Check if user is member of group
     */
    public function isMember($groupId, $userId) {
        $stmt = $this->db->prepare("
            SELECT id FROM `group_members` WHERE group_id = ? AND user_id = ?
        ");
        $stmt->execute([$groupId, $userId]);
        return (bool) $stmt->fetch();
    }
    
    /**
     * Create invitation and send email
     */
    public function createInvitation($groupId, $email, $invitedBy) {
        // Check if group is closed
        if ($this->isClosed($groupId)) {
            throw new Exception('Cannot invite members to a closed group');
        }
        
        // Check if email is already registered
        $user = new User();
        $isRegistered = $user->emailExists($email);
        
        // Check if user is already a member
        if ($isRegistered) {
            $existingUser = $user->getByEmail($email);
            if ($this->isMember($groupId, $existingUser['user_id'])) {
                throw new Exception('User is already a member of this group');
            }
        }
        
        // Get group info
        $group = $this->getById($groupId);
        if (!$group) {
            throw new Exception('Group not found');
        }
        
        // Get inviter info
        $inviter = $user->getById($invitedBy);
        if (!$inviter) {
            throw new Exception('Inviter not found');
        }
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        
        // Set expiration (7 days)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $stmt = $this->db->prepare("
            INSERT INTO `group_invitations` (group_id, email, invited_by, token, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$groupId, $email, $invitedBy, $token, $expiresAt]);
        
        // Send email
        $emailService = new Email();
        $emailService->sendInvitation(
            $email,
            $group['name'],
            $inviter['name'],
            $token,
            $isRegistered
        );
        
        return $token;
    }
    
    /**
     * Accept invitation
     */
    public function acceptInvitation($token, $userId) {
        // Get invitation
        $stmt = $this->db->prepare("
            SELECT gi.*, u.email as user_email
            FROM `group_invitations` gi
            INNER JOIN `users` u ON u.user_id = ?
            WHERE gi.token = ? AND gi.status = 'Pending' AND gi.expires_at > NOW()
        ");
        $stmt->execute([$userId, $token]);
        $invitation = $stmt->fetch();
        
        if (!$invitation) {
            throw new Exception('Invalid or expired invitation');
        }
        
        // Check email matches
        if ($invitation['email'] !== $invitation['user_email']) {
            throw new Exception('Invitation email does not match user email');
        }
        
        // Check if already a member
        if ($this->isMember($invitation['group_id'], $userId)) {
            // Already a member, just mark invitation as accepted
            $stmt = $this->db->prepare("
                UPDATE `group_invitations`
                SET status = 'Accepted'
                WHERE id = ?
            ");
            $stmt->execute([$invitation['id']]);
            return $invitation['group_id'];
        }
        
        // Add user to group
        $this->addMember($invitation['group_id'], $userId);
        
        // Update invitation status
        $stmt = $this->db->prepare("
            UPDATE `group_invitations`
            SET status = 'Accepted'
            WHERE id = ?
        ");
        $stmt->execute([$invitation['id']]);
        
        return $invitation['group_id'];
    }
    
    /**
     * Reject invitation
     */
    public function rejectInvitation($token, $userId) {
        // Get invitation
        $stmt = $this->db->prepare("
            SELECT gi.*, u.email as user_email
            FROM `group_invitations` gi
            INNER JOIN `users` u ON u.user_id = ?
            WHERE gi.token = ? AND gi.status = 'Pending' AND gi.expires_at > NOW()
        ");
        $stmt->execute([$userId, $token]);
        $invitation = $stmt->fetch();
        
        if (!$invitation) {
            throw new Exception('Invalid or expired invitation');
        }
        
        // Check email matches
        if ($invitation['email'] !== $invitation['user_email']) {
            throw new Exception('Invitation email does not match user email');
        }
        
        // Update invitation status
        $stmt = $this->db->prepare("
            UPDATE `group_invitations`
            SET status = 'Rejected'
            WHERE id = ?
        ");
        $stmt->execute([$invitation['id']]);
        
        return true;
    }
    
    /**
     * Get invitation by token
     */
    public function getInvitationByToken($token) {
        $stmt = $this->db->prepare("
            SELECT gi.*, g.name as group_name, u.name as inviter_name
            FROM `group_invitations` gi
            INNER JOIN `groups` g ON gi.group_id = g.group_id
            INNER JOIN `users` u ON gi.invited_by = u.user_id
            WHERE gi.token = ? AND gi.status = 'Pending' AND gi.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }
    
    /**
     * Get pending invitations for a user by email
     */
    public function getPendingInvitationsByEmail($email) {
        $stmt = $this->db->prepare("
            SELECT gi.*, g.name as group_name, g.description as group_description, 
                   g.type as group_type, u.name as inviter_name, u.email as inviter_email
            FROM `group_invitations` gi
            INNER JOIN `groups` g ON gi.group_id = g.group_id
            INNER JOIN `users` u ON gi.invited_by = u.user_id
            WHERE gi.email = ? AND gi.status = 'Pending' AND gi.expires_at > NOW()
            ORDER BY gi.created_at DESC
        ");
        $stmt->execute([$email]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get pending invitations for a user by user ID
     */
    public function getPendingInvitationsByUserId($userId) {
        $user = new User();
        $userData = $user->getById($userId);
        if (!$userData) {
            return [];
        }
        return $this->getPendingInvitationsByEmail($userData['email']);
    }
    
    /**
     * Close group
     */
    public function close($groupId, $userId) {
        // Verify user is creator
        $group = $this->getById($groupId);
        if ($group['creator_id'] != $userId) {
            throw new Exception('Only group creator can close the group');
        }
        
        $stmt = $this->db->prepare("
            UPDATE `groups`
            SET is_closed = TRUE
            WHERE group_id = ?
        ");
        $stmt->execute([$groupId]);
        
        return true;
    }
    
    /**
     * Check if group is closed
     */
    public function isClosed($groupId) {
        $group = $this->getById($groupId);
        if (!$group) {
            throw new Exception('Group not found');
        }
        return (bool) $group['is_closed'];
    }
    
    /**
     * Delete group (only creator can delete)
     */
    public function delete($groupId, $userId) {
        // Verify user is creator
        $group = $this->getById($groupId);
        if (!$group) {
            throw new Exception('Group not found');
        }
        
        if ($group['creator_id'] != $userId) {
            throw new Exception('Only group creator can delete the group');
        }
        
        // Delete group (cascade will handle related records)
        $stmt = $this->db->prepare("
            DELETE FROM `groups`
            WHERE group_id = ?
        ");
        $stmt->execute([$groupId]);
        
        return true;
    }
}

