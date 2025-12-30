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
        const registerContainer = document.getElementById('nova-x-register-container');
        const loginContainer = document.getElementById('nova-x-login-container');
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
    });
})();

