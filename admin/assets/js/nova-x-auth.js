/**
 * Nova-X Authentication JavaScript
 * 
 * Handles form submission and UI toggling for login/register forms.
 * 
 * @package Nova-X
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const registerForm = document.getElementById('nova-x-register-form');
        const loginForm = document.getElementById('nova-x-login-form');
        const registerContainer = document.getElementById('nx-register');
        const loginContainer = document.getElementById('nx-login');
        const registerMessage = document.getElementById('nova-x-register-message');
        const loginMessage = document.getElementById('nova-x-login-message');
        const switchToLogin = document.getElementById('switch-to-login');
        const switchToRegister = document.getElementById('switch-to-register');

        // Get REST API URL
        const restUrl = window.novaXAuth?.restUrl || '/wp-json/nova-x/v1';

        /**
         * Show message in message container
         */
        function showMessage(container, message, isError) {
            if (!container) return;
            
            container.textContent = message;
            container.className = 'nova-x-auth-message ' + (isError ? 'error' : 'success');
            container.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                container.style.display = 'none';
            }, 5000);
        }

        /**
         * Toggle between login and register forms
         */
        if (switchToLogin) {
            switchToLogin.addEventListener('click', function(e) {
                e.preventDefault();
                if (registerContainer) registerContainer.style.display = 'none';
                if (loginContainer) loginContainer.style.display = 'block';
            });
        }

        if (switchToRegister) {
            switchToRegister.addEventListener('click', function(e) {
                e.preventDefault();
                if (loginContainer) loginContainer.style.display = 'none';
                if (registerContainer) registerContainer.style.display = 'block';
            });
        }

        /**
         * Handle registration form submission
         */
        if (registerForm) {
            registerForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitButton = registerForm.querySelector('button[type="submit"]');
                const originalText = submitButton ? submitButton.textContent : '';
                
                // Disable submit button
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Registering...';
                }

                try {
                    const formData = new FormData(registerForm);
                    const data = Object.fromEntries(formData);
                    
                    const response = await fetch(restUrl + '/register', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        showMessage(registerMessage, '✅ ' + (result.message || 'Registration successful!'), false);
                        // Reload page after short delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        const errorMessage = result.message || result.code || 'Registration failed. Please try again.';
                        showMessage(registerMessage, '❌ ' + errorMessage, true);
                        
                        // Re-enable submit button
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.textContent = originalText;
                        }
                    }
                } catch (error) {
                    showMessage(registerMessage, '❌ Network error. Please check your connection and try again.', true);
                    
                    // Re-enable submit button
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                }
            });
        }

        /**
         * Handle login form submission
         */
        if (loginForm) {
            loginForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitButton = loginForm.querySelector('button[type="submit"]');
                const originalText = submitButton ? submitButton.textContent : '';
                
                // Disable submit button
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Logging in...';
                }

                try {
                    const formData = new FormData(loginForm);
                    const data = Object.fromEntries(formData);
                    
                    const response = await fetch(restUrl + '/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(data)
                    });

                    const result = await response.json();

                    if (result.success) {
                        showMessage(loginMessage, '✅ ' + (result.message || 'Login successful!'), false);
                        // Reload page after short delay
                        setTimeout(function() {
                            window.location.reload();
                        }, 1500);
                    } else {
                        const errorMessage = result.message || result.code || 'Invalid email or password.';
                        showMessage(loginMessage, '❌ ' + errorMessage, true);
                        
                        // Re-enable submit button
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.textContent = originalText;
                        }
                    }
                } catch (error) {
                    showMessage(loginMessage, '❌ Network error. Please check your connection and try again.', true);
                    
                    // Re-enable submit button
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                }
            });
        }

        /**
         * Handle account menu dropdown toggle
         */
        const toggle = document.getElementById('nx-account-toggle');
        const dropdown = document.getElementById('nx-account-dropdown');

        if (toggle && dropdown) {
            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('visible');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('visible');
                }
            });
        }

        /**
         * Handle logout from dropdown
         */
        const logoutLink = document.getElementById('nx-logout-link');
        if (logoutLink) {
            logoutLink.addEventListener('click', async function(e) {
                e.preventDefault();
                
                try {
                    const response = await fetch(restUrl + '/logout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    });
                    
                    // Reload page after logout
                    window.location.reload();
                } catch (error) {
                    console.error('Logout error:', error);
                    // Still reload on error
                    window.location.reload();
                }
            });
        }

        /**
         * Handle scroll to login/register forms from dropdown links
         */
        const loginLink = document.querySelector('#nx-account-dropdown a[href="#nx-login"]');
        const registerLink = document.querySelector('#nx-account-dropdown a[href="#nx-register"]');

        if (loginLink) {
            loginLink.addEventListener('click', function(e) {
                e.preventDefault();
                const loginContainer = document.getElementById('nx-login');
                const registerContainer = document.getElementById('nx-register');
                
                // Show login form, hide register
                if (registerContainer) {
                    registerContainer.style.display = 'none';
                }
                if (loginContainer) {
                    loginContainer.style.display = 'block';
                    loginContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                
                // Close dropdown
                if (dropdown) {
                    dropdown.classList.remove('visible');
                }
            });
        }

        if (registerLink) {
            registerLink.addEventListener('click', function(e) {
                e.preventDefault();
                const loginContainer = document.getElementById('nx-login');
                const registerContainer = document.getElementById('nx-register');
                
                // Show register form, hide login
                if (loginContainer) {
                    loginContainer.style.display = 'none';
                }
                if (registerContainer) {
                    registerContainer.style.display = 'block';
                    registerContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                
                // Close dropdown
                if (dropdown) {
                    dropdown.classList.remove('visible');
                }
            });
        }
    });
})();

