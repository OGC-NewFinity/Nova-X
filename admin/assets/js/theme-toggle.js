/**
 * Nova-X Global Theme Toggle
 * 
 * Handles light/dark mode switching across all plugin pages
 * 
 * @package Nova-X
 */

(function() {
    'use strict';

    /**
     * Global Theme Toggle Manager
     * Pure vanilla JavaScript implementation
     */
    const NovaXThemeToggle = {
        
        /**
         * Initialize theme toggle on page load
         */
        init: function() {
            this.applyInitialTheme();
            this.bindToggleEvents();
            this.watchSystemPreference();
        },

        /**
         * Get system theme preference
         * @returns {string} 'light' or 'dark'
         */
        getSystemTheme: function() {
            if (window.matchMedia) {
                // Check for dark mode preference first
                if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    return 'dark';
                }
                // Then check for light mode preference
                if (window.matchMedia('(prefers-color-scheme: light)').matches) {
                    return 'light';
                }
            }
            // Default fallback
            return 'dark';
        },

        /**
         * Apply initial theme based on saved preference, PHP default, or system preference
         */
        applyInitialTheme: function() {
            // Priority: localStorage > PHP default > system preference
            const savedTheme = localStorage.getItem('nova_x_theme_preference');
            let initialTheme = savedTheme;
            
            // If no saved preference, check PHP default (from wp_localize_script)
            if (!initialTheme && typeof novaXTheme !== 'undefined' && novaXTheme.defaultTheme) {
                initialTheme = novaXTheme.defaultTheme;
                // Store PHP default in localStorage for consistency
                localStorage.setItem('nova_x_theme_preference', initialTheme);
            }
            
            // Fallback to system preference if still no theme
            if (!initialTheme) {
                initialTheme = this.getSystemTheme();
                // Store system preference in localStorage
                localStorage.setItem('nova_x_theme_preference', initialTheme);
            }
            
            this.setTheme(initialTheme, false);
        },

        /**
         * Set theme across all elements
         * @param {string} theme Theme to apply ('light' or 'dark')
         * @param {boolean} save Whether to save to localStorage
         */
        setTheme: function(theme, save = true) {
            // Validate theme value
            if (theme !== 'light' && theme !== 'dark') {
                console.warn('Nova-X: Invalid theme value:', theme);
                return;
            }
            
            // Apply to document root (html element)
            if (document.documentElement) {
                document.documentElement.setAttribute('data-theme', theme);
            }
            
            // Apply to body
            if (document.body) {
                document.body.setAttribute('data-theme', theme);
            }
            
            // Apply to all Nova-X containers using native DOM with null checks
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
            this.updateThemeIcon(theme);
            
            // Save preference if requested
            if (save) {
                localStorage.setItem('nova_x_theme_preference', theme);
                
                // Optionally save to user meta via AJAX (for cross-device persistence)
                this.saveThemePreferenceToServer(theme);
            }
        },
        
        /**
         * Save theme preference to server via AJAX (optional)
         * @param {string} theme Theme to save
         */
        saveThemePreferenceToServer: function(theme) {
            // Check if REST API is available
            const restUrl = (typeof novaXDashboard !== 'undefined' && novaXDashboard.restUrl) 
                ? novaXDashboard.restUrl 
                : (typeof novaXTheme !== 'undefined' && novaXTheme.restUrl)
                    ? novaXTheme.restUrl
                    : null;
            
            if (!restUrl) {
                return; // No REST API available
            }
            
            const nonce = (typeof novaXDashboard !== 'undefined' && novaXDashboard.nonce)
                ? novaXDashboard.nonce
                : (typeof novaXTheme !== 'undefined' && novaXTheme.nonce)
                    ? novaXTheme.nonce
                    : '';
            
            // Use fetch API (vanilla JS)
            fetch(restUrl + 'update-theme-preference', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce,
                },
                body: JSON.stringify({
                    theme: theme,
                    nonce: nonce,
                }),
            }).catch(function() {
                // Silently fail - localStorage is sufficient
            });
        },

        /**
         * Update theme toggle icon based on current theme
         * @param {string} theme Current theme
         */
        updateThemeIcon: function(theme) {
            // Use getElementById for reliable element selection
            const lightIcon = document.getElementById('nova-x-theme-icon-light');
            const darkIcon = document.getElementById('nova-x-theme-icon-dark');
            
            // Null checks before manipulating
            if (!lightIcon || !darkIcon) {
                return; // Icons not found, skip update
            }
            
            // Toggle icon visibility based on theme
            if (theme === 'light') {
                lightIcon.style.display = 'block';
                darkIcon.style.display = 'none';
            } else {
                lightIcon.style.display = 'none';
                darkIcon.style.display = 'block';
            }
        },

        /**
         * Get current theme
         * @returns {string} Current theme
         */
        getCurrentTheme: function() {
            return document.documentElement.getAttribute('data-theme') || 
                   localStorage.getItem('nova_x_theme_preference') || 
                   this.getSystemTheme();
        },

        /**
         * Toggle between light and dark themes
         */
        toggleTheme: function() {
            const currentTheme = this.getCurrentTheme();
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            this.setTheme(newTheme, true);
        },

        /**
         * Bind click events to theme toggle buttons
         */
        bindToggleEvents: function() {
            const self = this;
            
            // Check if toggle button exists
            const toggleButton = document.getElementById('nova-x-theme-toggle');
            if (!toggleButton) {
                console.warn('Nova-X: Theme toggle button not found');
                return;
            }
            
            // Use native event listener for reliability
            toggleButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.toggleTheme();
            });
            
            // Also bind to data-theme-toggle attribute for flexibility
            const toggleButtons = document.querySelectorAll('[data-theme-toggle]');
            toggleButtons.forEach(function(btn) {
                if (btn !== toggleButton) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        self.toggleTheme();
                    });
                }
            });
        },

        /**
         * Watch for system theme preference changes
         */
        watchSystemPreference: function() {
            const self = this;
            
            if (!window.matchMedia) {
                return; // Not supported
            }
            
            // Watch for dark mode preference changes
            const darkMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            // Handle change event
            const handleChange = function() {
                // Only apply if user hasn't set a preference
                if (!localStorage.getItem('nova_x_theme_preference')) {
                    const systemTheme = self.getSystemTheme();
                    self.setTheme(systemTheme, false);
                }
            };
            
            // Modern browsers
            if (darkMediaQuery.addEventListener) {
                darkMediaQuery.addEventListener('change', handleChange);
            } else if (darkMediaQuery.addListener) {
                // Fallback for older browsers
                darkMediaQuery.addListener(handleChange);
            }
        }
    };

    // Initialize when DOM is ready
    function initializeThemeToggle() {
        // Check if toggle button exists
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
                console.warn('Nova-X: Theme toggle button not found after multiple attempts');
            }
            return;
        }
        
        // Reset retry count on success
        initializeThemeToggle.retryCount = 0;
        
        // Initialize theme toggle
        NovaXThemeToggle.init();
    }

    // Use DOMContentLoaded for reliable initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initializeThemeToggle();
        });
    } else {
        // DOM already loaded
        initializeThemeToggle();
    }

    // Expose globally for other scripts
    window.NovaXThemeToggle = NovaXThemeToggle;

})();

