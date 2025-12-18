/**
 * Expenses Module
 * Handles expense-related operations
 */

const Expenses = {
    /**
     * Create expense
     */
    async createExpense(data, receiptFile = null) {
        try {
            let response;
            
            if (receiptFile) {
                // Use FormData for file upload
                const formData = new FormData();
                formData.append('group_id', data.group_id);
                formData.append('paid_by_user_id', data.paid_by_user_id);
                formData.append('amount', data.amount);
                formData.append('description', data.description || '');
                formData.append('split_type', data.split_type);
                formData.append('expense_date', data.expense_date);
                formData.append('receipt', receiptFile);
                formData.append('splits', JSON.stringify(data.splits || []));
                
                response = await API.post('/expenses/create.php', formData, true);
            } else {
                response = await API.post('/expenses/create.php', data);
            }
            
            if (response.success) {
                return { success: true, expense: response.data };
            }
            return { success: false, message: response.message };
        } catch (error) {
            return { success: false, message: error.message || 'Failed to create expense' };
        }
    },
    
    /**
     * Get expenses for group
     */
    async getExpenses(groupId, page = 1, pageSize = 20) {
        try {
            const response = await API.get('/expenses/list.php', {
                group_id: groupId,
                page: page,
                page_size: pageSize
            });
            return response.success ? response.data : { expenses: [], total: 0 };
        } catch (error) {
            // Error shown in catch block - showError is async but we're in a non-async context
            // Let the caller handle errors
            return { expenses: [], total: 0 };
        }
    },
    
    /**
     * Delete expense
     */
    async deleteExpense(expenseId) {
        try {
            const response = await API.delete('/expenses/delete.php', { id: expenseId });
            if (response.success) {
                return { success: true };
            }
            return { success: false, message: response.message };
        } catch (error) {
            return { success: false, message: error.message || 'Failed to delete expense' };
        }
    }
};

