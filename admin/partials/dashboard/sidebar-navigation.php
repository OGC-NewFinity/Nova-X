<?php
/**
 * Sidebar Navigation Component
 * 
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Security check
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

// Get current tab from URL
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'generate';

// Define navigation items with icons
$nav_items = [
    'generate' => [
        'label' => esc_html__( 'Generate Theme', 'nova-x' ),
        'icon'  => 'dashicons-admin-generic',
    ],
    'customize' => [
        'label' => esc_html__( 'Customize Output', 'nova-x' ),
        'icon'  => 'dashicons-edit',
    ],
    'preview' => [
        'label' => esc_html__( 'Live Preview', 'nova-x' ),
        'icon'  => 'dashicons-visibility',
    ],
    'usage' => [
        'label' => esc_html__( 'Usage Stats', 'nova-x' ),
        'icon'  => 'dashicons-chart-line',
    ],
    'exported' => [
        'label' => esc_html__( 'Exported Themes', 'nova-x' ),
        'icon'  => 'dashicons-archive',
    ],
];
?>

<aside class="nova-x-sidebar" id="nova-x-sidebar">
    <!-- Sidebar Header with Logo and Toggle Icon -->
    <div class="nova-x-sidebar-header" style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px;">
        <img
            src="<?php echo esc_url( plugins_url( 'assets/images/logo/nova-x-logo-crystal-primary.png', dirname(__FILE__, 3) ) ); ?>"
            alt="Nova-X Logo"
            class="nova-x-logo"
            style="height: 36px; width: auto;"
        />

        <!-- Toggle Sidebar Button -->
        <button class="nova-x-sidebar-toggle" style="background: none; border: none; cursor: pointer;" onclick="document.body.classList.toggle('folded');">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
        </button>
    </div>
    
    <nav class="nova-x-sidebar-nav" id="nova-x-sidebar-nav">
        <ul class="nova-x-sidebar-menu">
            <?php foreach ( $nav_items as $tab_key => $item ) : ?>
                <li class="nova-x-sidebar-item">
                    <a href="#" 
                       class="nova-x-sidebar-link <?php echo $current_tab === $tab_key ? 'active' : ''; ?>" 
                       data-tab="<?php echo esc_attr( $tab_key ); ?>"
                       aria-label="<?php echo esc_attr( $item['label'] ); ?>">
                        <span class="nova-x-sidebar-icon dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
                        <span class="nova-x-sidebar-label"><?php echo esc_html( $item['label'] ); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>

