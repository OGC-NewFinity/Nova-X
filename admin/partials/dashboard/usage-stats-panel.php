<?php
/**
 * Usage Stats Panel Template
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

<div class="nova-x-tab-pane" id="nova-x-usage-tab">
    <h2><?php esc_html_e( 'Usage Statistics', 'nova-x' ); ?></h2>
    
    <!-- Stat Cards -->
    <div class="nova-x-usage-widgets">
        <div class="nova-x-widget">
            <div class="nova-x-widget-icon">
                <span class="dashicons dashicons-chart-line" style="font-size: 32px; width: 32px; height: 32px; color: #2271b1;"></span>
            </div>
            <h3><?php esc_html_e( 'Total Tokens Used', 'nova-x' ); ?></h3>
            <p class="nova-x-stat-large" id="nova-x-total-tokens">0</p>
        </div>
        
        <div class="nova-x-widget">
            <div class="nova-x-widget-icon">
                <span class="dashicons dashicons-money-alt" style="font-size: 32px; width: 32px; height: 32px; color: #00a32a;"></span>
            </div>
            <h3><?php esc_html_e( 'Total Estimated Cost', 'nova-x' ); ?></h3>
            <p class="nova-x-stat-large" id="nova-x-total-cost">$0.00 USD</p>
        </div>
    </div>

    <!-- Provider Usage Table -->
    <h3><?php esc_html_e( 'Per-Provider Breakdown', 'nova-x' ); ?></h3>
    
    <div class="nova-x-usage-table-wrapper">
        <div class="nova-x-loader nova-x-hidden" id="nova-x-usage-loader"></div>
        
        <table class="wp-list-table widefat fixed striped nova-x-usage-table" id="nova-x-provider-table">
            <thead>
                <tr>
                    <th style="width: 200px;"><?php esc_html_e( 'Provider', 'nova-x' ); ?></th>
                    <th style="text-align: right;"><?php esc_html_e( 'Tokens', 'nova-x' ); ?></th>
                    <th style="text-align: right;"><?php esc_html_e( 'Cost', 'nova-x' ); ?></th>
                    <th style="text-align: right;"><?php esc_html_e( '% of Usage', 'nova-x' ); ?></th>
                </tr>
            </thead>
            <tbody id="nova-x-provider-tbody">
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px; color: #999;">
                        <span class="nova-x-loading-text"><?php esc_html_e( 'Loading usage statistics...', 'nova-x' ); ?></span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Reset Button -->
    <div class="nova-x-usage-actions">
        <button type="button" class="button button-secondary" id="nova-x-reset-tracker">
            <span class="dashicons dashicons-update" style="vertical-align: middle; margin-right: 5px;"></span>
            <?php esc_html_e( 'Reset Tracker', 'nova-x' ); ?>
        </button>
        <span id="nova-x-reset-status" class="nova-x-status"></span>
    </div>
</div>

