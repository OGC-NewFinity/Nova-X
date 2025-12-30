<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_Account {
    public static function render_account_page() {
        // Load UI utilities
        $ui_utils_path = NOVA_X_PATH . 'admin/includes/ui-utils.php';
        if ( file_exists( $ui_utils_path ) ) {
            require_once $ui_utils_path;
        }
        
        // Get theme preference (default to dark)
        $theme = get_user_meta( get_current_user_id(), 'nova_x_theme_preference', true );
        if ( empty( $theme ) ) {
            $theme = 'dark';
        }
        
        $dashboard_url = admin_url( 'admin.php?page=nova-x-dashboard' );
        ?>
        <div class="wrap nova-x-wrapper nova-x-dashboard-wrap" data-theme="<?php echo esc_attr( $theme ); ?>">
            <div id="nova-x-wrapper" class="nova-x-wrapper">
                <div class="nova-x-dashboard-layout">
                    <div class="nova-x-dashboard-main nova-x-main">
                        <?php
                        // Render unified header (fixed overlay)
                        if ( function_exists( 'render_plugin_header' ) ) {
                            render_plugin_header( [
                                'notification_count' => 0,
                                'dashboard_url'      => $dashboard_url,
                            ] );
                        }
                        ?>
                        <div class="nova-x-page-content">
                            <h1>My Account</h1>
                            <p>Welcome to your Nova-X Account.</p>
                            <p>This page will include future account management features.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        // Include auth modal at page level if user is not logged in
        if ( ! Nova_X_Session::is_logged_in() ) {
            include NOVA_X_PATH . 'admin/partials/nova-x-auth-modal.php';
        }
    }
}

