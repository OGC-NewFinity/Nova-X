<?php
/**
 * Admin Header Layout
 * 
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load UI utilities
require_once plugin_dir_path( __FILE__ ) . '../../includes/ui-utils.php';

// Get notification count (can be customized)
$notification_count = apply_filters( 'nova_x_notification_count', 3 );

// Render header
render_plugin_header( [
    'notification_count' => $notification_count,
] );

