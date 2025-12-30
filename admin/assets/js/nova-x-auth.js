document.addEventListener('DOMContentLoaded', function () {
    // Modal open/close logic
    const trigger = document.getElementById('nova-x-auth-trigger');
    const modal = document.getElementById('nova-x-auth-modal');
    const closeBtn = modal?.querySelector('.nova-x-modal-close');
    const backdrop = modal?.querySelector('.nova-x-modal-backdrop');

    if (trigger && modal) {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        });
    }

    if (closeBtn && backdrop) {
        [closeBtn, backdrop].forEach(el =>
            el.addEventListener('click', () => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            })
        );
    }

    // Form handling
    const registerForm = document.getElementById('nova-x-register-form');
    const loginForm = document.getElementById('nova-x-login-form');
    const registerContainer = document.getElementById('nx-register');
    const loginContainer = document.getElementById('nx-login');
    const registerMessage = document.getElementById('nova-x-register-message');
    const loginMessage = document.getElementById('nova-x-login-message');
    const switchToLogin = document.getElementById('switch-to-login');
    const switchToRegister = document.getElementById('switch-to-register');

    const restUrl = window.novaXAuth?.restUrl || '/wp-json/nova-x/v1';

    function showMessage(container, message, isError) {
        if (!container) return;
        container.textContent = message;
        container.className = 'nova-x-auth-message ' + (isError ? 'error' : 'success');
        container.style.display = 'block';
        setTimeout(function() {
            container.style.display = 'none';
        }, 5000);
    }

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

    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitButton = registerForm.querySelector('button[type="submit"]');
            const originalText = submitButton ? submitButton.textContent : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Registering...';
            }
            try {
                const formData = new FormData(registerForm);
                const data = Object.fromEntries(formData);
                const response = await fetch(restUrl + '/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    showMessage(registerMessage, '✅ ' + (result.message || 'Registration successful!'), false);
                    setTimeout(function() { window.location.reload(); }, 1500);
                } else {
                    showMessage(registerMessage, '❌ ' + (result.message || result.code || 'Registration failed.'), true);
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                }
            } catch (error) {
                showMessage(registerMessage, '❌ Network error. Please try again.', true);
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
            }
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitButton = loginForm.querySelector('button[type="submit"]');
            const originalText = submitButton ? submitButton.textContent : '';
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Logging in...';
            }
            try {
                const formData = new FormData(loginForm);
                const data = Object.fromEntries(formData);
                const response = await fetch(restUrl + '/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    showMessage(loginMessage, '✅ ' + (result.message || 'Login successful!'), false);
                    setTimeout(function() { window.location.reload(); }, 1500);
                } else {
                    showMessage(loginMessage, '❌ ' + (result.message || result.code || 'Invalid credentials.'), true);
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = originalText;
                    }
                }
            } catch (error) {
                showMessage(loginMessage, '❌ Network error. Please try again.', true);
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
            }
        });
    }
});
