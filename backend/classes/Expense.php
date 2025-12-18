<?php
/**
 * Expense Class
 * Handles expense-related operations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Group.php';

class Expense {
    private $db;
    private $group;
    
    public function __construct() {
        $this->db = getDB();
        $this->group = new Group();
    }
    
    /**
     * Create expense with splits
     */
    public function create($groupId, $paidByUserId, $amount, $description, $splitType, $expenseDate, $splits, $receiptImage = null) {
        // Verify user is member of group
        if (!$this->group->isMember($groupId, $paidByUserId)) {
            throw new Exception('User is not a member of this group');
        }
        
        // Validate split type
        if (!in_array($splitType, ['Equal', 'Unequal', 'Shares'])) {
            throw new Exception('Invalid split type');
        }
        
        // Validate amount
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than 0');
        }
        
        // Process splits based on type
        $processedSplits = $this->processSplits($groupId, $amount, $splitType, $splits);
        
        // Insert expense
        $stmt = $this->db->prepare("
            INSERT INTO `expenses` (group_id, paid_by_user_id, amount, description, split_type, expense_date, receipt_image)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$groupId, $paidByUserId, $amount, $description, $splitType, $expenseDate, $receiptImage]);
        $expenseId = $this->db->lastInsertId();
        
        // Insert splits
        foreach ($processedSplits as $split) {
            $stmt = $this->db->prepare("
                INSERT INTO `expense_splits` (expense_id, user_id, amount, shares)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$expenseId, $split['user_id'], $split['amount'], $split['shares'] ?? 1]);
        }
        
        return $expenseId;
    }
    
    /**
     * Process splits based on type
     */
    private function processSplits($groupId, $totalAmount, $splitType, $splits) {
        $members = $this->group->getMembers($groupId);
        
        if ($splitType === 'Equal') {
            // Divide equally among all members
            $amountPerPerson = $totalAmount / count($members);
            $processed = [];
            foreach ($members as $member) {
                $processed[] = [
                    'user_id' => $member['user_id'],
                    'amount' => round($amountPerPerson, 2),
                    'shares' => 1
                ];
            }
            // Adjust last amount to account for rounding
            $sum = array_sum(array_column($processed, 'amount'));
            $difference = $totalAmount - $sum;
            if (abs($difference) > 0.01) {
                $processed[count($processed) - 1]['amount'] += $difference;
            }
            return $processed;
            
        } elseif ($splitType === 'Unequal') {
            // Custom amounts per member
            $totalSplitAmount = array_sum(array_column($splits, 'amount'));
            if (abs($totalSplitAmount - $totalAmount) > 0.01) {
                throw new Exception('Split amounts must equal expense amount');
            }
            return $splits;
            
        } elseif ($splitType === 'Shares') {
            // Proportional split based on shares
            $totalShares = array_sum(array_column($splits, 'shares'));
            if ($totalShares <= 0) {
                throw new Exception('Total shares must be greater than 0');
            }
            
            $processed = [];
            foreach ($splits as $split) {
                $amount = ($totalAmount * $split['shares']) / $totalShares;
                $processed[] = [
                    'user_id' => $split['user_id'],
                    'amount' => round($amount, 2),
                    'shares' => $split['shares']
                ];
            }
            // Adjust last amount to account for rounding
            $sum = array_sum(array_column($processed, 'amount'));
            $difference = $totalAmount - $sum;
            if (abs($difference) > 0.01) {
                $processed[count($processed) - 1]['amount'] += $difference;
            }
            return $processed;
        }
        
        throw new Exception('Invalid split type');
    }
    
    /**
     * Get expense by ID
     */
    public function getById($expenseId) {
        $stmt = $this->db->prepare("
            SELECT e.*, u.name as paid_by_name, u.email as paid_by_email
            FROM `expenses` e
            LEFT JOIN `users` u ON e.paid_by_user_id = u.user_id
            WHERE e.expense_id = ?
        ");
        $stmt->execute([$expenseId]);
        $expense = $stmt->fetch();
        
        if ($expense) {
            $expense['splits'] = $this->getSplits($expenseId);
        }
        
        return $expense;
    }
    
    /**
     * Get splits for expense
     */
    public function getSplits($expenseId) {
        $stmt = $this->db->prepare("
            SELECT es.*, u.name as user_name, u.email as user_email
            FROM `expense_splits` es
            INNER JOIN `users` u ON es.user_id = u.user_id
            WHERE es.expense_id = ?
        ");
        $stmt->execute([$expenseId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get expenses for group
     */
    public function getGroupExpenses($groupId, $page = 1, $pageSize = 20) {
        $offset = ($page - 1) * $pageSize;
        
        // Cast to integers for safety (already validated in calling code)
        $pageSize = (int) $pageSize;
        $offset = (int) $offset;
        
        // Get total count
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM `expenses` WHERE group_id = ?");
        $stmt->execute([$groupId]);
        $total = $stmt->fetch()['total'];
        
        // Get expenses (LIMIT/OFFSET cannot be bound as parameters with native prepared statements)
        $stmt = $this->db->prepare("
            SELECT e.*, u.name as paid_by_name
            FROM `expenses` e
            LEFT JOIN `users` u ON e.paid_by_user_id = u.user_id
            WHERE e.group_id = ?
            ORDER BY e.expense_date DESC, e.created_at DESC
            LIMIT {$pageSize} OFFSET {$offset}
        ");
        $stmt->execute([$groupId]);
        $expenses = $stmt->fetchAll();
        
        // Add splits to each expense
        foreach ($expenses as &$expense) {
            $expense['splits'] = $this->getSplits($expense['expense_id']);
        }
        
        return [
            'expenses' => $expenses,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ];
    }
    
    /**
     * Delete expense
     */
    public function delete($expenseId, $userId) {
        // Verify expense exists and user has permission
        $expense = $this->getById($expenseId);
        if (!$expense) {
            throw new Exception('Expense not found');
        }
        
        // Only allow deletion by person who paid or group creator
        $group = $this->group->getById($expense['group_id']);
        if ($expense['paid_by_user_id'] != $userId && $group['creator_id'] != $userId) {
            throw new Exception('Permission denied');
        }
        
        // Delete splits first (cascade should handle this, but explicit is better)
        $stmt = $this->db->prepare("DELETE FROM `expense_splits` WHERE expense_id = ?");
        $stmt->execute([$expenseId]);
        
        // Delete expense
        $stmt = $this->db->prepare("DELETE FROM `expenses` WHERE expense_id = ?");
        $stmt->execute([$expenseId]);
        
        return true;
    }
}

