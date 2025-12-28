<?php
/**
 * Live Preview Panel Template
 * 
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Security check
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nova-x' ) );
}
?>

<div class="nova-x-tab-pane" id="nova-x-preview-tab">
    <h2><?php esc_html_e( 'Live Preview', 'nova-x' ); ?></h2>
    
    <div class="nova-x-preview-container">
        <!-- Placeholder when no preview is available -->
        <div id="nova-x-preview-placeholder" class="nova-x-preview-placeholder">
            <div class="nova-x-preview-icon">
                <span class="dashicons dashicons-visibility" style="font-size: 64px; width: 64px; height: 64px; color: #646970;"></span>
            </div>
            <h3><?php esc_html_e( 'No Preview Available', 'nova-x' ); ?></h3>
            <p><?php esc_html_e( 'Generate a theme to enable preview.', 'nova-x' ); ?></p>
        </div>

        <!-- Load Preview Button (initially hidden) -->
        <div id="nova-x-preview-actions" class="nova-x-preview-actions nova-x-hidden">
            <button type="button" class="button button-primary button-large" id="nova-x-load-preview">
                <span class="dashicons dashicons-visibility" style="vertical-align: middle; margin-right: 5px;"></span>
                <?php esc_html_e( 'Load Preview', 'nova-x' ); ?>
            </button>
            <span id="nova-x-preview-status" class="nova-x-status"></span>
            <div class="nova-x-loader nova-x-hidden" id="nova-x-preview-loader"></div>
        </div>

        <!-- Preview Frame (initially hidden) -->
        <div id="nova-x-preview-frame-wrapper" class="nova-x-preview-frame-wrapper nova-x-hidden">
            <div class="nova-x-preview-header">
                <h3><?php esc_html_e( 'Theme Preview', 'nova-x' ); ?></h3>
                <button type="button" class="button button-secondary" id="nova-x-refresh-preview">
                    <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>
                    <?php esc_html_e( 'Refresh', 'nova-x' ); ?>
                </button>
            </div>
            <iframe 
                id="nova-x-preview-frame" 
                src="" 
                frameborder="0"
                sandbox="allow-same-origin allow-scripts allow-forms allow-popups"
                title="<?php esc_attr_e( 'Theme Preview', 'nova-x' ); ?>"
            ></iframe>
            <div id="nova-x-preview-error" class="nova-x-preview-error nova-x-hidden">
                <p>
                    <span class="dashicons dashicons-warning" style="color: #d63638; vertical-align: middle;"></span>
                    <strong><?php esc_html_e( 'Preview failed to load.', 'nova-x' ); ?></strong>
                    <?php esc_html_e( 'Please check that the theme is properly installed and try again.', 'nova-x' ); ?>
                </p>
            </div>
        </div>
    </div>
</div>

