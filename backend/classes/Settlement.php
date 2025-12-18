<?php
/**
 * Settlement Class
 * Handles settlement calculations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Group.php';

class Settlement {
    private $db;
    private $group;
    
    public function __construct() {
        $this->db = getDB();
        $this->group = new Group();
    }
    
    /**
     * Calculate balances for all members in group
     */
    public function calculateBalances($groupId) {
        $members = $this->group->getMembers($groupId);
        $balances = [];
        
        // Initialize balances
        foreach ($members as $member) {
            $balances[$member['user_id']] = [
                'user_id' => $member['user_id'],
                'name' => $member['name'],
                'paid' => 0,
                'owed' => 0,
                'balance' => 0
            ];
        }
        
        // Calculate total paid by each member
        $stmt = $this->db->prepare("
            SELECT paid_by_user_id, SUM(amount) as total_paid
            FROM `expenses`
            WHERE group_id = ?
            GROUP BY paid_by_user_id
        ");
        $stmt->execute([$groupId]);
        while ($row = $stmt->fetch()) {
            if (isset($balances[$row['paid_by_user_id']])) {
                $balances[$row['paid_by_user_id']]['paid'] = (float) $row['total_paid'];
            }
        }
        
        // Calculate total owed by each member
        $stmt = $this->db->prepare("
            SELECT es.user_id, SUM(es.amount) as total_owed
            FROM `expense_splits` es
            INNER JOIN `expenses` e ON es.expense_id = e.expense_id
            WHERE e.group_id = ?
            GROUP BY es.user_id
        ");
        $stmt->execute([$groupId]);
        while ($row = $stmt->fetch()) {
            if (isset($balances[$row['user_id']])) {
                $balances[$row['user_id']]['owed'] = (float) $row['total_owed'];
            }
        }
        
        // Calculate net balance
        foreach ($balances as &$balance) {
            $balance['balance'] = $balance['paid'] - $balance['owed'];
        }
        
        return array_values($balances);
    }
    
    /**
     * Calculate optimized settlement transactions
     */
    public function calculateSettlements($groupId) {
        $balances = $this->calculateBalances($groupId);
        
        // Separate debtors (negative balance) and creditors (positive balance)
        $debtors = [];
        $creditors = [];
        
        foreach ($balances as $balance) {
            $roundedBalance = round($balance['balance'], 2);
            if ($roundedBalance < 0) {
                $debtors[] = [
                    'user_id' => $balance['user_id'],
                    'name' => $balance['name'],
                    'amount' => abs($roundedBalance)
                ];
            } elseif ($roundedBalance > 0) {
                $creditors[] = [
                    'user_id' => $balance['user_id'],
                    'name' => $balance['name'],
                    'amount' => $roundedBalance
                ];
            }
        }
        
        // Sort by amount (largest first)
        usort($debtors, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        usort($creditors, function($a, $b) {
            return $b['amount'] <=> $a['amount'];
        });
        
        // Match debts to credits
        $settlements = [];
        $debtorIndex = 0;
        $creditorIndex = 0;
        
        while ($debtorIndex < count($debtors) && $creditorIndex < count($creditors)) {
            $debt = $debtors[$debtorIndex];
            $credit = $creditors[$creditorIndex];
            
            if ($debt['amount'] <= $credit['amount']) {
                // Debt can be fully covered
                $settlements[] = [
                    'from_user_id' => $debt['user_id'],
                    'from_name' => $debt['name'],
                    'to_user_id' => $credit['user_id'],
                    'to_name' => $credit['name'],
                    'amount' => round($debt['amount'], 2)
                ];
                
                $creditors[$creditorIndex]['amount'] -= $debt['amount'];
                $debtorIndex++;
                
                if ($creditors[$creditorIndex]['amount'] < 0.01) {
                    $creditorIndex++;
                }
            } else {
                // Partial payment
                $settlements[] = [
                    'from_user_id' => $debt['user_id'],
                    'from_name' => $debt['name'],
                    'to_user_id' => $credit['user_id'],
                    'to_name' => $credit['name'],
                    'amount' => round($credit['amount'], 2)
                ];
                
                $debtors[$debtorIndex]['amount'] -= $credit['amount'];
                $creditorIndex++;
            }
        }
        
        return [
            'balances' => $balances,
            'settlements' => $settlements
        ];
    }
}

