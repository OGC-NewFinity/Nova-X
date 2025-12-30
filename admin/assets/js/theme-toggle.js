/**
 * Nova-X Global Theme Toggle
 * 
 * Handles light/dark mode switching across all plugin pages
 * Syncs with user meta via REST API for cross-device persistence
 * 
 * @package Nova-X
 */

(function() {
    'use strict';

    /**
     * Apply theme to document
     * @param {string} theme Theme to apply ('light' or 'dark')
     */
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        
        // Also apply to body and Nova-X containers for compatibility
        if (document.body) {
            document.body.setAttribute('data-theme', theme);
        }
        
        const selectors = [
            '.nova-x-wrapper',
            '.nova-x-dashboard-wrap',
            '.nova-x-header-overlay',
            '.nova-x-header-bar'
        ];
        
        selectors.forEach(function(selector) {
            const elements = document.querySelectorAll(selector);
            elements.forEach(function(element) {
                if (element) {
                    element.setAttribute('data-theme', theme);
                }
            });
        });
        
        // Update icon visibility
        updateThemeIcon(theme);
    }

    /**
     * Update theme toggle icon based on current theme
     * @param {string} theme Current theme
     */
    function updateThemeIcon(theme) {
        const lightIcon = document.getElementById('nova-x-theme-icon-light');
        const darkIcon = document.getElementById('nova-x-theme-icon-dark');
        
        if (!lightIcon || !darkIcon) {
            return;
        }
        
        if (theme === 'light') {
            lightIcon.style.display = 'block';
            darkIcon.style.display = 'none';
        } else {
            lightIcon.style.display = 'none';
            darkIcon.style.display = 'block';
        }
    }

    /**
     * Load user theme preference from REST API if localStorage is missing
     * Prevents flicker by applying default theme first, then updating from REST API
     */
    async function loadUserThemePreference() {
        const local = localStorage.getItem('nova_x_theme');
        if (local) {
            applyTheme(local);
            return;
        }

        // Apply default theme immediately to prevent flicker
        applyTheme('dark');

        // Check if REST API data is available
        if (typeof NovaXTheme === 'undefined' || !NovaXTheme.restUrl) {
            return;
        }

        try {
            const res = await fetch(NovaXTheme.restUrl, {
                headers: {
                    'X-WP-Nonce': NovaXTheme.nonce
                }
            });
            const data = await res.json();
            if (data && data.theme) {
                localStorage.setItem('nova_x_theme', data.theme);
                // Only update if different from default
                if (data.theme !== 'dark') {
                    applyTheme(data.theme);
                }
            }
        } catch (e) {
            console.warn('Failed to load theme preference:', e);
        }
    }

    /**
     * Toggle theme and save to both localStorage and REST API
     */
    async function toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(current);
        localStorage.setItem('nova_x_theme', current);

        // Check if REST API data is available
        if (typeof NovaXTheme === 'undefined' || !NovaXTheme.restUrl) {
            return;
        }

        try {
            await fetch(NovaXTheme.restUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': NovaXTheme.nonce
                },
                body: JSON.stringify({ theme: current })
            });
        } catch (e) {
            console.warn('Failed to save theme preference:', e);
        }
    }

    /**
     * Watch for system theme preference changes
     */
    function watchSystemPreference() {
        if (!window.matchMedia) {
            return;
        }
        
        const darkMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        const handleChange = function() {
            // Only apply if user hasn't set a preference
            if (!localStorage.getItem('nova_x_theme')) {
                const systemTheme = darkMediaQuery.matches ? 'dark' : 'light';
                applyTheme(systemTheme);
            }
        };
        
        if (darkMediaQuery.addEventListener) {
            darkMediaQuery.addEventListener('change', handleChange);
        } else if (darkMediaQuery.addListener) {
            darkMediaQuery.addListener(handleChange);
        }
    }

    /**
     * Bind click events to theme toggle buttons
     */
    function bindToggleEvents() {
        const toggleButton = document.getElementById('nova-x-theme-toggle');
        if (toggleButton) {
            toggleButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleTheme();
            });
        }
        
        // Also bind to data-theme-toggle attribute for flexibility
        const toggleButtons = document.querySelectorAll('[data-theme-toggle]');
        toggleButtons.forEach(function(btn) {
            if (btn !== toggleButton) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleTheme();
                });
            }
        });
    }

    /**
     * Initialize theme toggle on page load
     */
    function initializeThemeToggle() {
        const toggleButton = document.getElementById('nova-x-theme-toggle');
        if (!toggleButton) {
            // Retry after a short delay if element doesn't exist yet (max 5 retries = 500ms)
            if (typeof initializeThemeToggle.retryCount === 'undefined') {
                initializeThemeToggle.retryCount = 0;
            }
            if (initializeThemeToggle.retryCount < 5) {
                initializeThemeToggle.retryCount++;
                setTimeout(initializeThemeToggle, 100);
            } else {
                // Even if button doesn't exist, still load theme preference
                loadUserThemePreference();
            }
            return;
        }
        
        // Reset retry count on success
        initializeThemeToggle.retryCount = 0;
        
        // Load theme preference first
        loadUserThemePreference();
        
        // Bind toggle events
        bindToggleEvents();
        
        // Watch system preference changes
        watchSystemPreference();
    }

    // Load theme immediately to prevent flicker (before DOM is ready)
    // This runs synchronously if possible, or as early as possible
    if (document.readyState === 'loading') {
        // If still loading, try to load theme immediately
        // Use requestAnimationFrame to ensure DOM is ready but before paint
        requestAnimationFrame(function() {
            loadUserThemePreference();
        });
        
        // Also initialize on DOMContentLoaded for full setup
        document.addEventListener('DOMContentLoaded', function() {
            initializeThemeToggle();
        });
    } else {
        // DOM already loaded
        loadUserThemePreference();
        initializeThemeToggle();
    }

    // Expose globally for other scripts
    window.NovaXThemeToggle = {
        toggle: toggleTheme,
        applyTheme: applyTheme,
        loadPreference: loadUserThemePreference
    };

})();
