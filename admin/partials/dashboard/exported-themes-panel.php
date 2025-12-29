<?php
/**
 * Exported Themes Panel Template
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

<div class="nova-x-tab-pane" id="nova-x-exported-themes-tab">
    <h2><?php esc_html_e( 'Exported Themes', 'nova-x' ); ?></h2>
    
    <p class="nova-x-exported-themes-help">
        <?php esc_html_e( 'Manage your previously exported themes. You can preview, install, delete, or re-export any theme from this list.', 'nova-x' ); ?>
    </p>

    <!-- Loading State -->
    <div class="nova-x-exported-themes-loader-wrapper">
        <div class="nova-x-loader nova-x-hidden" id="nova-x-exported-themes-loader"></div>
    </div>

    <!-- Empty State -->
    <div class="nova-x-exported-themes-empty nova-x-hidden" id="nova-x-exported-themes-empty">
        <div class="nova-x-empty-icon">
            <span class="dashicons dashicons-archive nova-x-icon-text" style="font-size: 64px; width: 64px; height: 64px;"></span>
        </div>
        <h3><?php esc_html_e( 'No Exported Themes', 'nova-x' ); ?></h3>
        <p><?php esc_html_e( 'You haven\'t exported any themes yet. Generate and export a theme to see it here.', 'nova-x' ); ?></p>
    </div>

    <!-- Themes Table -->
    <div class="nova-x-exported-themes-table-wrapper" id="nova-x-exported-themes-table-wrapper">
        <table class="wp-list-table widefat fixed striped nova-x-exported-themes-table" id="nova-x-exported-themes-table">
            <thead>
                <tr>
                    <th style="width: 30%;"><?php esc_html_e( 'Theme Name', 'nova-x' ); ?></th>
                    <th style="width: 20%;"><?php esc_html_e( 'Date Exported', 'nova-x' ); ?></th>
                    <th style="width: 15%;"><?php esc_html_e( 'Size', 'nova-x' ); ?></th>
                    <th style="width: 35%; text-align: center;"><?php esc_html_e( 'Actions', 'nova-x' ); ?></th>
                </tr>
            </thead>
            <tbody id="nova-x-exported-themes-tbody">
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px;" class="nova-x-loading-text">
                        <span class="nova-x-loading-text"><?php esc_html_e( 'Loading exported themes...', 'nova-x' ); ?></span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Status Messages -->
    <div id="nova-x-exported-themes-status" class="nova-x-status"></div>
</div>

