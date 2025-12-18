/**
 * Groups Module
 * Handles group-related operations
 */

const Groups = {
    /**
     * Get all groups for current user
     */
    async getGroups() {
        try {
            const response = await API.get('/groups/list.php');
            return response.success ? response.data : [];
        } catch (error) {
            console.error('Error fetching groups:', error);
            Utils.showError('Failed to load groups');
            return [];
        }
    },
    
    /**
     * Get group details
     */
    async getGroup(groupId) {
        try {
            const response = await API.get('/groups/get.php', { id: groupId });
            return response.success ? response.data : null;
        } catch (error) {
            console.error('Error fetching group:', error);
            Utils.showError('Failed to load group');
            return null;
        }
    },
    
    /**
     * Create new group
     */
    async createGroup(data) {
        try {
            const response = await API.post('/groups/create.php', data);
            if (response.success) {
                return { success: true, group: response.data };
            }
            return { success: false, message: response.message };
        } catch (error) {
            console.error('Error creating group:', error);
            return { success: false, message: error.message || 'Failed to create group' };
        }
    },
    
    /**
     * Invite user to group
     */
    async inviteUser(groupId, email) {
        try {
            const response = await API.post('/groups/invite.php', {
                group_id: groupId,
                email: email
            });
            if (response.success) {
                return { success: true, data: response.data };
            }
            return { success: false, message: response.message };
        } catch (error) {
            console.error('Error inviting user:', error);
            return { success: false, message: error.message || 'Failed to invite user' };
        }
    },
    
    /**
     * Close group
     */
    async closeGroup(groupId) {
        try {
            const response = await API.put('/groups/close.php', {
                group_id: groupId
            });
            if (response.success) {
                return { success: true };
            }
            return { success: false, message: response.message };
        } catch (error) {
            console.error('Error closing group:', error);
            return { success: false, message: error.message || 'Failed to close group' };
        }
    },
    
    /**
     * Delete group
     */
    async deleteGroup(groupId) {
        try {
            const response = await API.delete('/groups/delete.php', {
                group_id: groupId
            });
            if (response.success) {
                return { success: true };
            }
            return { success: false, message: response.message };
        } catch (error) {
            console.error('Error deleting group:', error);
            return { success: false, message: error.message || 'Failed to delete group' };
        }
    },
    
    /**
     * Render group card
     */
    renderGroupCard(group) {
        const card = document.createElement('div');
        card.className = 'group-card';
        card.onclick = () => {
            window.location.href = `/frontend/groups/details.html?id=${group.group_id}`;
        };
        
        const typeClass = group.type.toLowerCase().replace(' ', '-');
        const totalAmount = group.total_amount || 0;
        
        const isClosed = group.is_closed == 1 || group.is_closed === true;
        
        card.innerHTML = `
            <div class="group-card-header">
                <h3 class="group-card-title">${Utils.escapeHtml(group.name)}</h3>
                <div style="display: flex; gap: var(--spacing-xs); align-items: center;">
                    <span class="group-card-type ${typeClass}">${group.type}</span>
                    ${isClosed ? '<span class="badge badge-warning" style="font-size: 0.75rem;">Closed</span>' : ''}
                </div>
            </div>
            ${group.description ? `<p class="group-card-description">${Utils.escapeHtml(group.description)}</p>` : ''}
            <div class="group-card-meta">
                <span>Created by ${Utils.escapeHtml(group.creator_name || 'Unknown')}</span>
                <span class="group-card-amount">${Utils.formatCurrency(totalAmount)}</span>
            </div>
        `;
        
        return card;
    }
};

// Add escapeHtml to Utils if not present
if (!Utils.escapeHtml) {
    Utils.escapeHtml = function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };
}

