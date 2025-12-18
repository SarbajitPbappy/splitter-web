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
            console.error('Error adding meal:', error);
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
            console.error('Error fetching meals:', error);
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
            console.error('Error calculating meal costs:', error);
            return null;
        }
    },
    
    /**
     * Record market expense
     */
    async recordMarketExpense(groupId, monthYear, totalAmount) {
        try {
            // Note: This endpoint would need to be created in the backend
            // For now, this is a placeholder
            return { success: false, message: 'Feature not yet implemented' };
        } catch (error) {
            console.error('Error recording market expense:', error);
            return { success: false, message: error.message };
        }
    }
};

