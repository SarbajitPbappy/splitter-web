/**
 * Authentication Module
 * Handles login, logout, token management, and session validation
 */

const Auth = {
    /**
     * Check if user is authenticated
     */
    isAuthenticated() {
        return !!localStorage.getItem('auth_token');
    },
    
    /**
     * Get current user token
     */
    getToken() {
        return localStorage.getItem('auth_token');
    },
    
    /**
     * Get current user data
     */
    getUser() {
        const userStr = localStorage.getItem('user_data');
        return userStr ? JSON.parse(userStr) : null;
    },
    
    /**
     * Set user data
     */
    setUser(userData) {
        if (userData) {
            localStorage.setItem('user_data', JSON.stringify(userData));
        } else {
            localStorage.removeItem('user_data');
        }
    },
    
    /**
     * Login user
     */
    async login(email, password) {
        try {
            const response = await API.post('/auth/login.php', {
                email: email,
                password: password
            });
            
            console.log('Login API response:', response);
            
            // Check if response has the expected structure
            if (response && response.success === true) {
                if (response.data) {
                    if (response.data.token) {
                        // Save token
                        API.setToken(response.data.token);
                        console.log('Token saved to localStorage');
                        
                        // Save user data if available
                        if (response.data.user) {
                            this.setUser(response.data.user);
                            console.log('User data saved:', response.data.user);
                        }
                        
                        return { 
                            success: true, 
                            user: response.data.user || null 
                        };
                    } else {
                        console.error('No token in response data:', response.data);
                        return { 
                            success: false, 
                            message: response.message || 'Login failed: No token received' 
                        };
                    }
                } else {
                    console.error('No data in response:', response);
                    return { 
                        success: false, 
                        message: response.message || 'Login failed: Invalid response format' 
                    };
                }
            } else {
                // Response indicates failure
                const errorMsg = response?.message || 'Login failed';
                console.error('Login failed:', errorMsg);
                return { success: false, message: errorMsg };
            }
        } catch (error) {
            console.error('Login error:', error);
            return { 
                success: false, 
                message: error.message || 'Login failed: Network or server error' 
            };
        }
    },
    
    /**
     * Register new user
     */
    async register(name, email, password, inviteToken = null) {
        try {
            const payload = {
                name: name,
                email: email,
                password: password
            };
            
            // Add invite token if provided
            if (inviteToken) {
                payload.invite_token = inviteToken;
            }
            
            const response = await API.post('/auth/register.php', payload);
            
            if (response.success) {
                // After registration, automatically login
                const loginResult = await this.login(email, password);
                
                // If invite token was provided and accepted, include group_id in result
                if (loginResult.success && response.group_id) {
                    loginResult.group_id = response.group_id;
                }
                
                return loginResult;
            }
            
            return { success: false, message: response.message || 'Registration failed' };
        } catch (error) {
            console.error('Registration error:', error);
            return { success: false, message: error.message || 'Registration failed' };
        }
    },
    
    /**
     * Logout user
     */
    async logout() {
        try {
            // Try to call logout endpoint
            await API.post('/auth/logout.php');
        } catch (error) {
            console.error('Logout API error:', error);
            // Continue with local logout even if API call fails
        } finally {
            // Clear local storage
            API.setToken(null);
            this.setUser(null);
            // Redirect to login
            window.location.href = '/frontend/login.html';
        }
    },
    
    /**
     * Verify token with server
     */
    async verifyToken() {
        if (!this.isAuthenticated()) {
            return false;
        }
        
        try {
            const response = await API.get('/auth/verify_token.php');
            if (response.success && response.data.user) {
                this.setUser(response.data.user);
                return true;
            }
            return false;
        } catch (error) {
            console.error('Token verification error:', error);
            // Token invalid, clear it
            this.logout();
            return false;
        }
    },
    
    /**
     * Require authentication - redirect to login if not authenticated
     */
    async requireAuth() {
        if (!this.isAuthenticated()) {
            console.log('Not authenticated, redirecting to login');
            window.location.href = '/frontend/login.html';
            return false;
        }
        
        // Try to verify token, but don't block if it fails (API will handle it)
        try {
            const isValid = await this.verifyToken();
            if (!isValid) {
                console.log('Token invalid, redirecting to login');
                window.location.href = '/frontend/login.html';
                return false;
            }
            return true;
        } catch (error) {
            console.error('Token verification error:', error);
            // If verification fails but token exists, allow access
            // The API endpoints will handle invalid tokens
            return true;
        }
    },
    
    /**
     * Redirect to login if already authenticated (for login/register pages)
     */
    redirectIfAuthenticated() {
        if (this.isAuthenticated()) {
            window.location.replace('/frontend/dashboard.html');
        }
    }
};

