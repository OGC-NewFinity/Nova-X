/**
 * Nova-X Notices JavaScript Bridge
 * 
 * Provides JavaScript API for displaying dynamic notices in the admin interface
 * 
 * @package Nova-X
 */

(function($) {
    'use strict';

    /**
     * NovaX Notices JavaScript API
     */
    const NovaXNotices = {
        
        /**
         * Display a notice dynamically
         * 
         * @param {string} message - The notice message
         * @param {string} type - Notice type: 'success', 'error', 'info', 'warning'
         * @param {boolean} dismissible - Whether the notice is dismissible
         * @param {string} container - CSS selector for container (default: '.nova-x-page-content')
         */
        show: function(message, type, dismissible, container) {
            type = type || 'info';
            dismissible = dismissible !== false;
            container = container || '.nova-x-page-content';
            
            // Create notice HTML
            const dismissibleClass = dismissible ? ' is-dismissible' : '';
            const icon = this.getIcon(type);
            const dismissButton = dismissible ? 
                '<button type="button" class="nova-x-notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' : 
                '';
            
            const noticeHTML = `
                <div class="nova-x-notice nova-x-notice-${type}${dismissibleClass}">
                    ${icon}
                    <p>${this.escapeHtml(message)}</p>
                    ${dismissButton}
                </div>
            `;
            
            // Find container and prepend notice
            const $container = $(container);
            if ($container.length) {
                $container.prepend(noticeHTML);
            } else {
                // Fallback to body if container not found
                $('body').prepend(noticeHTML);
            }
            
            // Attach dismiss handler if dismissible
            if (dismissible) {
                this.attachDismissHandler();
            }
        },
        
        /**
         * Display success notice
         */
        success: function(message, dismissible, container) {
            this.show(message, 'success', dismissible, container);
        },
        
        /**
         * Display error notice
         */
        error: function(message, dismissible, container) {
            this.show(message, 'error', dismissible, container);
        },
        
        /**
         * Display info notice
         */
        info: function(message, dismissible, container) {
            this.show(message, 'info', dismissible, container);
        },
        
        /**
         * Display warning notice
         */
        warning: function(message, dismissible, container) {
            this.show(message, 'warning', dismissible, container);
        },
        
        /**
         * Get icon HTML for notice type
         */
        getIcon: function(type) {
            const icons = {
                success: '<span class="nova-x-notice-icon dashicons dashicons-yes-alt"></span>',
                error: '<span class="nova-x-notice-icon dashicons dashicons-dismiss"></span>',
                info: '<span class="nova-x-notice-icon dashicons dashicons-info"></span>',
                warning: '<span class="nova-x-notice-icon dashicons dashicons-warning"></span>'
            };
            
            return icons[type] || icons.info;
        },
        
        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },
        
        /**
         * Attach dismiss handler to dismissible notices
         */
        attachDismissHandler: function() {
            $(document).off('click', '.nova-x-notice-dismiss').on('click', '.nova-x-notice-dismiss', function(e) {
                e.preventDefault();
                const $notice = $(this).closest('.nova-x-notice');
                $notice.addClass('nova-x-notice-dismissed');
                
                // Remove from DOM after animation
                setTimeout(function() {
                    $notice.remove();
                }, 300);
            });
        },
        
        /**
         * Remove all notices
         */
        clear: function(container) {
            container = container || '.nova-x-page-content';
            $(container).find('.nova-x-notice').each(function() {
                const $notice = $(this);
                $notice.addClass('nova-x-notice-dismissed');
                setTimeout(function() {
                    $notice.remove();
                }, 300);
            });
        }
    };
    
    // Make NovaXNotices available globally
    window.NovaXNotices = NovaXNotices;
    
    // Attach dismiss handlers on document ready
    $(document).ready(function() {
        NovaXNotices.attachDismissHandler();
    });
    
})(jQuery);

