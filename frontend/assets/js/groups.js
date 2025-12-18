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
            // Error shown in catch block - showError is async but we're in a non-async context
            // Let the caller handle errors
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
            // Error shown in catch block - showError is async but we're in a non-async context
            // Let the caller handle errors
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
            return { success: false, message: error.message || 'Failed to delete group' };
        }
    },
    
    /**
     * Get pending invitations for current user
     */
    async getPendingInvitations() {
        try {
            const response = await API.get('/groups/pending_invitations.php');
            return response.success ? response.data : [];
        } catch (error) {
            return [];
        }
    },
    
    /**
     * Render invitation card
     */
    renderInvitationCard(invitation) {
        const card = document.createElement('div');
        card.className = 'group-card';
        card.style.marginBottom = 'var(--spacing-md)';
        
        const typeClass = invitation.group_type.toLowerCase().replace(' ', '-');
        
        card.innerHTML = `
            <div class="group-card-header">
                <div>
                    <h3 class="group-card-title">${Utils.escapeHtml(invitation.group_name)}</h3>
                    <p style="color: var(--text-secondary); font-size: var(--font-size-sm); margin-top: var(--spacing-xs);">
                        Invited by ${Utils.escapeHtml(invitation.inviter_name)}
                    </p>
                </div>
                <span class="group-card-type ${typeClass}">${invitation.group_type}</span>
            </div>
            ${invitation.group_description ? `<p class="group-card-description">${Utils.escapeHtml(invitation.group_description)}</p>` : ''}
            <div style="display: flex; gap: var(--spacing-sm); margin-top: var(--spacing-md);">
                <button class="btn btn-primary btn-sm accept-invitation-btn" data-token="${Utils.escapeHtml(invitation.token)}">
                    Accept
                </button>
                <button class="btn btn-outline btn-sm reject-invitation-btn" data-token="${Utils.escapeHtml(invitation.token)}">
                    Reject
                </button>
            </div>
            <p style="font-size: var(--font-size-xs); color: var(--text-secondary); margin-top: var(--spacing-sm);">
                Expires: ${Utils.formatDate(invitation.expires_at)}
            </p>
        `;
        
        // Add event listeners
        card.querySelector('.accept-invitation-btn').addEventListener('click', async (e) => {
            e.stopPropagation();
            const token = e.target.dataset.token;
            await this.handleAcceptInvitation(token);
        });
        
        card.querySelector('.reject-invitation-btn').addEventListener('click', async (e) => {
            e.stopPropagation();
            const token = e.target.dataset.token;
            await this.handleRejectInvitation(token);
        });
        
        return card;
    },
    
    /**
     * Handle accepting invitation
     */
    async handleAcceptInvitation(token) {
        const confirmed = await Utils.confirm(
            'Accept Invitation?',
            'Do you want to join this group?',
            'Yes, Accept',
            'Cancel'
        );
        if (!confirmed) {
            return;
        }
        
        try {
            const response = await API.post('/groups/accept_invitation.php', { token: token });
            if (response.success) {
                await Utils.showSuccess('Invitation accepted! Redirecting to group...');
                const groupId = response.data.group_id || response.data.group?.group_id;
                setTimeout(() => {
                    window.location.href = `/frontend/groups/details.html?id=${groupId}`;
                }, 1500);
            } else {
                await Utils.showError(response.message || 'Failed to accept invitation');
            }
        } catch (error) {
            await Utils.showError(error.message || 'Failed to accept invitation');
        }
    },
    
    /**
     * Handle rejecting invitation
     */
    async handleRejectInvitation(token) {
        const confirmed = await Utils.confirm(
            'Reject Invitation?',
            'Are you sure you want to reject this invitation?',
            'Yes, Reject',
            'Cancel'
        );
        if (!confirmed) {
            return;
        }
        
        try {
            const response = await API.post('/groups/reject_invitation.php', { token: token });
            if (response.success) {
                await Utils.showSuccess('Invitation rejected');
                // Reload page to refresh invitations list
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                await Utils.showError(response.message || 'Failed to reject invitation');
            }
        } catch (error) {
            await Utils.showError(error.message || 'Failed to reject invitation');
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

