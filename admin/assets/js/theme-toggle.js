/**
 * Nova-X Global Theme Toggle
 * 
 * Handles light/dark mode switching across all plugin pages
 * 
 * @package Nova-X
 */

(function($) {
    'use strict';

    /**
     * Global Theme Toggle Manager
     * Works with or without jQuery
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
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
                return 'light';
            }
            return 'dark';
        },

        /**
         * Apply initial theme based on saved preference or system preference
         */
        applyInitialTheme: function() {
            const savedTheme = localStorage.getItem('nova_x_theme_preference');
            const initialTheme = savedTheme || this.getSystemTheme();
            
            this.setTheme(initialTheme, false);
        },

        /**
         * Set theme across all elements
         * @param {string} theme Theme to apply ('light' or 'dark')
         * @param {boolean} save Whether to save to localStorage
         */
        setTheme: function(theme, save = true) {
            // Apply to document root
            document.documentElement.setAttribute('data-theme', theme);
            
            // Apply to body
            if (document.body) {
                document.body.setAttribute('data-theme', theme);
            }
            
            // Apply to all Nova-X containers using native DOM
            const containers = document.querySelectorAll('.nova-x-dashboard-wrap, .nova-x-header-bar, .nova-x-header-overlay');
            containers.forEach(function(container) {
                container.setAttribute('data-theme', theme);
            });
            
            // Update icon visibility
            this.updateThemeIcon(theme);
            
            // Save preference if requested
            if (save) {
                localStorage.setItem('nova_x_theme_preference', theme);
            }
        },

        /**
         * Update theme toggle icon based on current theme
         * @param {string} theme Current theme
         */
        updateThemeIcon: function(theme) {
            // Use native DOM methods for reliability
            const lightIcon = document.getElementById('nova-x-theme-icon-light') || 
                             document.querySelector('.theme-icon-light');
            const darkIcon = document.getElementById('nova-x-theme-icon-dark') || 
                            document.querySelector('.theme-icon-dark');
            
            if (lightIcon && darkIcon) {
                if (theme === 'light') {
                    lightIcon.style.display = 'block';
                    darkIcon.style.display = 'none';
                } else {
                    lightIcon.style.display = 'none';
                    darkIcon.style.display = 'block';
                }
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
            
            if (window.matchMedia) {
                const mediaQuery = window.matchMedia('(prefers-color-scheme: light)');
                
                // Handle change event
                const handleChange = function(e) {
                    // Only apply if user hasn't set a preference
                    if (!localStorage.getItem('nova_x_theme_preference')) {
                        const systemTheme = e.matches ? 'light' : 'dark';
                        self.setTheme(systemTheme, false);
                    }
                };
                
                // Modern browsers
                if (mediaQuery.addEventListener) {
                    mediaQuery.addEventListener('change', handleChange);
                } else {
                    // Fallback for older browsers
                    mediaQuery.addListener(handleChange);
                }
            }
        }
    };

    // Initialize when DOM is ready
    function initializeThemeToggle() {
        // Check if toggle button exists
        const toggleButton = document.getElementById('nova-x-theme-toggle');
        if (!toggleButton) {
            // Retry after a short delay if element doesn't exist yet
            setTimeout(initializeThemeToggle, 100);
            return;
        }
        
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

    // Also use jQuery ready as fallback
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function() {
            initializeThemeToggle();
        });
    }

    // Expose globally for other scripts
    window.NovaXThemeToggle = NovaXThemeToggle;

})(typeof jQuery !== 'undefined' ? jQuery : null);

