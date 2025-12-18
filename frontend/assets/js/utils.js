/**
 * Utility Functions
 * Common helper functions for formatting, validation, and DOM manipulation
 */

const Utils = {
    /**
     * Format date for display
     */
    formatDate(date, format = 'YYYY-MM-DD') {
        if (!date) return '';
        
        const d = new Date(date);
        if (isNaN(d.getTime())) return date; // Return as-is if invalid
        
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        
        switch (format) {
            case 'YYYY-MM-DD':
                return `${year}-${month}-${day}`;
            case 'MM/DD/YYYY':
                return `${month}/${day}/${year}`;
            case 'DD/MM/YYYY':
                return `${day}/${month}/${year}`;
            case 'full':
                return d.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            default:
                return `${year}-${month}-${day}`;
        }
    },
    
    /**
     * Format currency (BDT - Bangladeshi Taka)
     */
    formatCurrency(amount, currency = 'BDT') {
        const num = parseFloat(amount);
        if (isNaN(num)) return '৳0.00';
        // Format as BDT with Taka symbol
        return '৳' + num.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    },
    
    /**
     * Format number
     */
    formatNumber(num, decimals = 2) {
        const number = parseFloat(num);
        if (isNaN(number)) return '0';
        return number.toFixed(decimals);
    },
    
    /**
     * Get current month in YYYY-MM format
     */
    getCurrentMonth() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        return `${year}-${month}`;
    },
    
    /**
     * Validate email format
     */
    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    /**
     * Validate password strength
     */
    validatePassword(password) {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
        const re = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
        return re.test(password);
    },
    
    /**
     * Show loading spinner
     */
    showLoading(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (element) {
            element.classList.add('loading');
            element.disabled = true;
        }
    },
    
    /**
     * Hide loading spinner
     */
    hideLoading(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }
        if (element) {
            element.classList.remove('loading');
            element.disabled = false;
        }
    },
    
    /**
     * Show error message using SweetAlert
     */
    async showError(message, title = 'Error') {
        return Swal.fire({
            icon: 'error',
            title: title,
            text: message,
            confirmButtonColor: '#f44336',
            confirmButtonText: 'OK'
        });
    },
    
    /**
     * Show success message using SweetAlert
     */
    async showSuccess(message, title = 'Success') {
        return Swal.fire({
            icon: 'success',
            title: title,
            text: message,
            confirmButtonColor: '#4CAF50',
            confirmButtonText: 'OK',
            timer: 3000,
            timerProgressBar: true
        });
    },
    
    /**
     * Show info message using SweetAlert
     */
    async showInfo(message, title = 'Information') {
        return Swal.fire({
            icon: 'info',
            title: title,
            text: message,
            confirmButtonColor: '#2196F3',
            confirmButtonText: 'OK'
        });
    },
    
    /**
     * Show warning message using SweetAlert
     */
    async showWarning(message, title = 'Warning') {
        return Swal.fire({
            icon: 'warning',
            title: title,
            text: message,
            confirmButtonColor: '#FF9800',
            confirmButtonText: 'OK'
        });
    },
    
    /**
     * Show confirmation dialog using SweetAlert
     */
    async confirm(title, text, confirmText = 'Yes', cancelText = 'Cancel') {
        const result = await Swal.fire({
            title: title,
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#4CAF50',
            cancelButtonColor: '#f44336',
            confirmButtonText: confirmText,
            cancelButtonText: cancelText
        });
        return result.isConfirmed;
    },
    
    /**
     * Show confirmation dialog for delete actions
     */
    async confirmDelete(itemName = 'this item') {
        return this.confirm(
            'Are you sure?',
            `This will permanently delete ${itemName}. This action cannot be undone!`,
            'Yes, delete it',
            'Cancel'
        );
    },
    
    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    /**
     * Get URL parameter
     */
    getURLParam(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    },
    
    /**
     * Set URL parameter
     */
    setURLParam(name, value) {
        const url = new URL(window.location);
        url.searchParams.set(name, value);
        window.history.pushState({}, '', url);
    },
    
    /**
     * Format relative time (e.g., "2 hours ago")
     */
    formatRelativeTime(date) {
        const now = new Date();
        const then = new Date(date);
        const diffMs = now - then;
        const diffSecs = Math.floor(diffMs / 1000);
        const diffMins = Math.floor(diffSecs / 60);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);
        
        if (diffSecs < 60) return 'just now';
        if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
        if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
        if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
        
        return this.formatDate(date, 'MM/DD/YYYY');
    },
    
    /**
     * Truncate text
     */
    truncate(text, maxLength) {
        if (!text || text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    },
    
    /**
     * Copy text to clipboard
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            // Silently fail - clipboard access may not be available
            return false;
        }
    }
};

