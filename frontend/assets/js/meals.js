/**
 * Meals Module
 * Handles meal tracking operations
 */

const Meals = {
    /**
     * Add meal entry
     */
    async addMeal(groupId, mealDate, mealType, mealCategory) {
        try {
            const response = await API.post('/meals/add.php', {
                group_id: groupId,
                meal_date: mealDate,
                meal_type: mealType,
                meal_category: mealCategory
            });
            if (response.success) {
                return { success: true };
            }
            return { success: false, message: response.message };
        } catch (error) {
            return { success: false, message: error.message || 'Failed to add meal' };
        }
    },
    
    /**
     * Get meals for group and month
     */
    async getMeals(groupId, monthYear) {
        try {
            const response = await API.get('/meals/list.php', {
                group_id: groupId,
                month: monthYear
            });
            return response.success ? response.data : [];
        } catch (error) {
            return [];
        }
    },
    
    /**
     * Calculate meal costs
     */
    async calculateMealCosts(groupId, monthYear) {
        try {
            const response = await API.get('/meals/calculate.php', {
                group_id: groupId,
                month: monthYear
            });
            return response.success ? response.data : null;
        } catch (error) {
            return null;
        }
    },
    
    /**
     * Record market expense
     */
    async recordMarketExpense(groupId, monthYear, totalAmount) {
        try {
            const response = await API.post('/meals/market_expense.php', {
                group_id: groupId,
                month_year: monthYear,
                total_amount: totalAmount
            });
            if (response.success) {
                return { success: true };
            }
            return { success: false, message: response.message };
        } catch (error) {
            return { success: false, message: error.message || 'Failed to record market expense' };
        }
    },
    
    /**
     * Delete meal entry
     */
    async deleteMeal(mealId) {
        try {
            const response = await API.delete('/meals/delete.php', {
                meal_id: mealId
            });
            if (response.success) {
                return { success: true };
            }
            return { success: false, message: response.message };
        } catch (error) {
            return { success: false, message: error.message || 'Failed to delete meal' };
        }
    }
};

