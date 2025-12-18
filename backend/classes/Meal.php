<?php
/**
 * Meal Class
 * Handles meal tracking operations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Group.php';
require_once __DIR__ . '/Expense.php';

class Meal {
    private $db;
    private $group;
    private $expense;
    
    public function __construct() {
        $this->db = getDB();
        $this->group = new Group();
        $this->expense = new Expense();
    }
    
    /**
     * Add meal entry
     */
    public function add($groupId, $userId, $mealDate, $mealType, $mealCategory) {
        // Verify user is member
        if (!$this->group->isMember($groupId, $userId)) {
            throw new Exception('User is not a member of this group');
        }
        
        // Validate meal type
        if (!in_array($mealType, ['Breakfast', 'Lunch', 'Dinner'])) {
            throw new Exception('Invalid meal type');
        }
        
        // Validate meal category
        if (!in_array($mealCategory, ['Mess Meal', 'Outside Meal'])) {
            throw new Exception('Invalid meal category');
        }
        
        $stmt = $this->db->prepare("
            INSERT INTO `meals` (group_id, user_id, meal_date, meal_type, meal_category)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$groupId, $userId, $mealDate, $mealType, $mealCategory]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Get meals for group and month
     */
    public function getMeals($groupId, $monthYear) {
        // monthYear format: YYYY-MM
        $startDate = $monthYear . '-01';
        $endDate = date('Y-m-t', strtotime($startDate)); // Last day of month
        
        $stmt = $this->db->prepare("
            SELECT m.*, u.name as user_name
            FROM `meals` m
            INNER JOIN `users` u ON m.user_id = u.user_id
            WHERE m.group_id = ? AND m.meal_date BETWEEN ? AND ?
            ORDER BY m.meal_date ASC, m.meal_type ASC
        ");
        $stmt->execute([$groupId, $startDate, $endDate]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get meal count per user for month
     */
    public function getMealCounts($groupId, $monthYear) {
        $startDate = $monthYear . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $stmt = $this->db->prepare("
            SELECT user_id, COUNT(*) as meal_count
            FROM `meals`
            WHERE group_id = ? AND meal_date BETWEEN ? AND ? AND meal_category = 'Mess Meal'
            GROUP BY user_id
        ");
        $stmt->execute([$groupId, $startDate, $endDate]);
        
        $counts = [];
        while ($row = $stmt->fetch()) {
            $counts[$row['user_id']] = $row['meal_count'];
        }
        
        return $counts;
    }
    
    /**
     * Calculate meal costs for Bachelor Mess
     * Contributions are automatically calculated from expenses paid by each person
     */
    public function calculateMessCosts($groupId, $monthYear) {
        $startDate = $monthYear . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        
        // Get market expenses for month
        $stmt = $this->db->prepare("
            SELECT total_amount
            FROM `market_expenses`
            WHERE group_id = ? AND month_year = ?
        ");
        $stmt->execute([$groupId, $monthYear]);
        $marketExpense = $stmt->fetch();
        $totalMarketExpense = $marketExpense ? (float) $marketExpense['total_amount'] : 0;
        
        // Get total meals
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_meals
            FROM `meals`
            WHERE group_id = ? AND meal_date BETWEEN ? AND ? AND meal_category = 'Mess Meal'
        ");
        $stmt->execute([$groupId, $startDate, $endDate]);
        $result = $stmt->fetch();
        $totalMeals = (int) $result['total_meals'];
        
        // Calculate cost per meal (only if market expense and meals exist)
        $costPerMeal = ($totalMarketExpense > 0 && $totalMeals > 0) ? $totalMarketExpense / $totalMeals : 0;
        
        // Get meal counts per user
        $mealCounts = $this->getMealCounts($groupId, $monthYear);
        
        // Get total expenses paid by each user in the month (contributions)
        // This is automatically calculated from all expenses where user paid
        $members = $this->group->getMembers($groupId);
        $userContributions = [];
        $expenseDetails = [];
        
        $stmt = $this->db->prepare("
            SELECT paid_by_user_id, SUM(amount) as total_paid, COUNT(*) as expense_count
            FROM `expenses`
            WHERE group_id = ? AND expense_date BETWEEN ? AND ?
            GROUP BY paid_by_user_id
        ");
        $stmt->execute([$groupId, $startDate, $endDate]);
        while ($row = $stmt->fetch()) {
            $userContributions[$row['paid_by_user_id']] = (float) $row['total_paid'];
            $expenseDetails[$row['paid_by_user_id']] = [
                'total_paid' => (float) $row['total_paid'],
                'expense_count' => (int) $row['expense_count']
            ];
        }
        
        // Calculate total contributions
        $totalContributions = array_sum($userContributions);
        
        // Calculate balances
        $userBalances = [];
        foreach ($members as $member) {
            $userId = $member['user_id'];
            $mealsEaten = $mealCounts[$userId] ?? 0;
            $contribution = $userContributions[$userId] ?? 0;
            $expenseCount = $expenseDetails[$userId]['expense_count'] ?? 0;
            $totalOwed = $mealsEaten * $costPerMeal;
            $balance = $contribution - $totalOwed;
            
            $userBalances[] = [
                'user_id' => $userId,
                'name' => $member['name'],
                'meals_eaten' => $mealsEaten,
                'cost_per_meal' => round($costPerMeal, 2),
                'total_owed' => round($totalOwed, 2),
                'contribution' => round($contribution, 2),
                'contribution_from_expenses' => round($contribution, 2), // Make it clear it's from expenses
                'expense_count' => $expenseCount, // Number of expenses paid
                'balance' => round($balance, 2)
            ];
        }
        
        return [
            'total_market_expense' => round($totalMarketExpense, 2),
            'total_contributions' => round($totalContributions, 2), // Total from all expenses
            'total_meals' => $totalMeals,
            'cost_per_meal' => round($costPerMeal, 2),
            'user_balances' => $userBalances
        ];
    }
    
    /**
     * Record market expense for month
     */
    public function recordMarketExpense($groupId, $monthYear, $totalAmount, $recordedBy) {
        // Check if group is closed
        if ($this->group->isClosed($groupId)) {
            throw new Exception('Cannot record market expenses for a closed group');
        }
        
        // Check if already recorded
        $stmt = $this->db->prepare("
            SELECT id FROM `market_expenses`
            WHERE group_id = ? AND month_year = ?
        ");
        $stmt->execute([$groupId, $monthYear]);
        if ($stmt->fetch()) {
            // Update existing
            $stmt = $this->db->prepare("
                UPDATE `market_expenses`
                SET total_amount = ?, recorded_by = ?
                WHERE group_id = ? AND month_year = ?
            ");
            $stmt->execute([$totalAmount, $recordedBy, $groupId, $monthYear]);
        } else {
            // Insert new
            $stmt = $this->db->prepare("
                INSERT INTO `market_expenses` (group_id, month_year, total_amount, recorded_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$groupId, $monthYear, $totalAmount, $recordedBy]);
        }
        
        return true;
    }
}

