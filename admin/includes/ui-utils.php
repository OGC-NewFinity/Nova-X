<?php
/**
 * UI Utility Functions
 * 
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the Nova-X plugin header
 * 
 * @param array $args {
 *     Optional. Array of arguments.
 *     @type int    $notification_count Notification count for badge.
 *     @type string $logo_url           Logo image URL.
 *     @type string $dashboard_url      Dashboard URL.
 * }
 */
function render_plugin_header( $args = [] ) {
    $defaults = [
        'notification_count' => 0,
        'logo_url'          => plugin_dir_url( NOVA_X_PLUGIN_FILE ) . 'assets/images/logo/nova-x-logo-crystal-primary.png',
        'dashboard_url'      => admin_url( 'admin.php?page=nova-x-dashboard' ),
    ];
    
    $args = wp_parse_args( $args, $defaults );
    
    // Get current theme preference
    $current_theme = get_user_meta( get_current_user_id(), 'nova_x_theme_preference', true );
    if ( empty( $current_theme ) ) {
        $current_theme = 'dark';
    }
    
    ?>
    <div class="nova-x-header-overlay" data-theme="<?php echo esc_attr( $current_theme ); ?>">
        <div class="nova-x-header-bar">
            <div class="nova-x-header-left">
                <a href="<?php echo esc_url( $args['dashboard_url'] ); ?>" class="nova-x-header-logo-link" aria-label="Nova-X Dashboard">
                    <img src="<?php echo esc_url( $args['logo_url'] ); ?>" alt="Nova-X" class="nova-x-header-logo" />
                </a>
            </div>
            
            <div class="nova-x-header-right">
            <!-- Profile Icon -->
            <a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>" class="icon-button nova-x-profile-link" aria-label="Profile">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" fill="currentColor"/>
                    <path d="M12.0002 14.5C6.99016 14.5 2.91016 17.86 2.91016 22C2.91016 22.28 3.13016 22.5 3.41016 22.5H20.5902C20.8702 22.5 21.0902 22.28 21.0902 22C21.0902 17.86 17.0102 14.5 12.0002 14.5Z" fill="currentColor"/>
                </svg>
            </a>
            
            <!-- Notifications Icon -->
            <div class="nova-x-header-control nova-x-notifications-dropdown">
                <button type="button" class="icon-button" id="nova-x-notifications-btn" aria-label="Notifications" aria-expanded="false">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C8.13 2 5 5.13 5 9C5 14.25 2.81 16.66 2.81 16.66L3.19 18.04C3.45 18.81 4.09 19.29 4.82 19.29H19.18C19.91 19.29 20.55 18.81 20.81 18.04L21.19 16.66C21.19 16.66 19 14.25 19 9C19 5.13 15.87 2 12 2Z" fill="currentColor"/>
                        <path d="M13.73 21C13.5542 21.3031 13.3019 21.5547 12.9982 21.7295C12.6946 21.9044 12.3504 21.9965 12 21.9965C11.6496 21.9965 11.3054 21.9044 11.0018 21.7295C10.6982 21.5547 10.4458 21.3031 10.27 21H13.73Z" fill="currentColor"/>
                    </svg>
                    <?php if ( $args['notification_count'] > 0 ) : ?>
                        <span class="nova-x-badge" id="nova-x-notifications-badge"><?php echo esc_html( $args['notification_count'] ); ?></span>
                    <?php endif; ?>
                </button>
                <div class="nova-x-dropdown-menu" id="nova-x-notifications-menu">
                    <div class="nova-x-dropdown-header">
                        <h3>Notifications</h3>
                    </div>
                    <div class="nova-x-dropdown-content">
                        <a href="#" class="nova-x-notification-item">
                            <div class="nova-x-notification-icon">
                                <span class="dashicons dashicons-yes-alt"></span>
                            </div>
                            <div class="nova-x-notification-content">
                                <div class="nova-x-notification-title">Theme generated successfully</div>
                                <div class="nova-x-notification-time">2 minutes ago</div>
                            </div>
                        </a>
                        <a href="#" class="nova-x-notification-item">
                            <div class="nova-x-notification-icon">
                                <span class="dashicons dashicons-info"></span>
                            </div>
                            <div class="nova-x-notification-content">
                                <div class="nova-x-notification-title">New feature available</div>
                                <div class="nova-x-notification-time">1 hour ago</div>
                            </div>
                        </a>
                        <a href="#" class="nova-x-notification-item">
                            <div class="nova-x-notification-icon">
                                <span class="dashicons dashicons-update"></span>
                            </div>
                            <div class="nova-x-notification-content">
                                <div class="nova-x-notification-title">System update available</div>
                                <div class="nova-x-notification-time">3 hours ago</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Theme Toggle -->
            <div class="nova-x-header-control">
                <button type="button" class="icon-button" id="nova-x-theme-toggle" aria-label="Toggle Theme" data-theme-toggle>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" id="nova-x-theme-icon-light" class="theme-icon-light">
                        <circle cx="12" cy="12" r="5" fill="currentColor"/>
                        <path d="M12 2V4M12 20V22M4 12H2M6.31412 6.31412L4.8999 4.8999M17.6859 6.31412L19.1001 4.8999M6.31412 17.69L4.8999 19.1042M17.6859 17.69L19.1001 19.1042M22 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" id="nova-x-theme-icon-dark" class="theme-icon-dark" style="display: none;">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
            
            <!-- Upgrade Button -->
            <a href="#" class="btn-upgrade btn-upgrade-placeholder" id="nova-x-upgrade-link" title="Upgrade features coming soon." aria-label="Upgrade (Coming Soon)">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13 3L4 14H11L11 21L20 10H13L13 3Z" fill="currentColor"/>
                </svg>
                <span>Upgrade</span>
            </a>
            </div>
        </div>
    </div>
    <?php
}

