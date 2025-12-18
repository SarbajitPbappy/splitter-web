/**
 * Analytics Module
 * Handles analytics and settlement operations
 */

const Analytics = {
    /**
     * Get dashboard analytics
     */
    async getDashboard(groupId) {
        try {
            const response = await API.get('/analytics/dashboard.php', {
                group_id: groupId
            });
            return response.success ? response.data : null;
        } catch (error) {
            return null;
        }
    },
    
    /**
     * Get settlement calculations
     */
    async getSettlement(groupId) {
        try {
            const response = await API.get('/analytics/settlement.php', {
                group_id: groupId
            });
            return response.success ? response.data : null;
        } catch (error) {
            return null;
        }
    },
    
    /**
     * Generate PDF report
     */
    async generatePDF(groupId) {
        try {
            const url = `/backend/api/pdf/generate.php?group_id=${groupId}`;
            const token = API.getToken();
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const downloadUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = downloadUrl;
                a.download = `group_report_${groupId}_${new Date().toISOString().split('T')[0]}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(downloadUrl);
                document.body.removeChild(a);
                return { success: true };
            } else {
                const error = await response.json();
                return { success: false, message: error.message || 'Failed to generate PDF' };
            }
        } catch (error) {
            return { success: false, message: error.message || 'Failed to generate PDF' };
        }
    }
};

