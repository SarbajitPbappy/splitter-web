/**
 * API Communication Module
 * Handles all API calls with JWT token management
 */

const API = {
    baseURL: '/backend/api',
    
    /**
     * Get JWT token from localStorage
     */
    getToken() {
        return localStorage.getItem('auth_token');
    },
    
    /**
     * Set JWT token in localStorage
     */
    setToken(token) {
        if (token) {
            localStorage.setItem('auth_token', token);
        } else {
            localStorage.removeItem('auth_token');
        }
    },
    
    /**
     * Get headers for API request
     */
    getHeaders(includeAuth = true) {
        const headers = {
            'Content-Type': 'application/json'
        };
        
        if (includeAuth) {
            const token = this.getToken();
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }
        }
        
        return headers;
    },
    
    /**
     * Handle API response
     */
    async handleResponse(response) {
        const contentType = response.headers.get('content-type');
        
        // Handle PDF or other binary responses
        if (contentType && contentType.includes('application/pdf')) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `report_${Date.now()}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            return { success: true };
        }
        
        // Get response text first to handle JSON parsing errors
        const text = await response.text();
        let data;
        
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse JSON response:', text);
            throw new Error('Invalid JSON response from server');
        }
        
        // If response is not OK, throw error with message
        if (!response.ok) {
            const errorMessage = data.message || data.error || `HTTP error! status: ${response.status}`;
            throw new Error(errorMessage);
        }
        
        return data;
    },
    
    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        const url = new URL(this.baseURL + endpoint, window.location.origin);
        Object.keys(params).forEach(key => {
            if (params[key] !== null && params[key] !== undefined) {
                url.searchParams.append(key, params[key]);
            }
        });
        
        try {
            const response = await fetch(url, {
                method: 'GET',
                headers: this.getHeaders()
            });
            
            return await this.handleResponse(response);
        } catch (error) {
            console.error('API GET error:', error);
            throw error;
        }
    },
    
    /**
     * POST request
     */
    async post(endpoint, data = {}, useFormData = false) {
        const options = {
            method: 'POST'
        };
        
        if (useFormData) {
            options.body = data; // FormData
            // For FormData, let browser set Content-Type with boundary
            const headers = {};
            const token = this.getToken();
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }
            options.headers = headers;
        } else {
            options.headers = this.getHeaders();
            options.body = JSON.stringify(data);
        }
        
        try {
            const url = this.baseURL + endpoint;
            console.log('API POST:', url, useFormData ? '(FormData)' : data);
            const response = await fetch(url, options);
            const result = await this.handleResponse(response);
            console.log('API POST response:', result);
            return result;
        } catch (error) {
            console.error('API POST error:', error);
            throw error;
        }
    },
    
    /**
     * PUT request
     */
    async put(endpoint, data = {}) {
        try {
            const response = await fetch(this.baseURL + endpoint, {
                method: 'PUT',
                headers: this.getHeaders(),
                body: JSON.stringify(data)
            });
            
            return await this.handleResponse(response);
        } catch (error) {
            console.error('API PUT error:', error);
            throw error;
        }
    },
    
    /**
     * DELETE request
     */
    async delete(endpoint, data = {}) {
        try {
            const response = await fetch(this.baseURL + endpoint, {
                method: 'DELETE',
                headers: this.getHeaders(),
                body: JSON.stringify(data)
            });
            
            return await this.handleResponse(response);
        } catch (error) {
            console.error('API DELETE error:', error);
            throw error;
        }
    }
};

