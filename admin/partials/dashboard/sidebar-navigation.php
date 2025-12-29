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
    <!-- Nova-X Sidebar Header -->
    <div class="nova-x-sidebar-header">
      <button id="novaX_sidebar_toggle" class="nova-x-toggle-btn" title="Toggle Sidebar" aria-label="Toggle Sidebar">
        &#9776;
      </button>
    </div>
    
    <nav class="nova-x-sidebar-nav" id="nova-x-sidebar-nav">
        <ul class="nova-x-sidebar-menu">
            <?php foreach ( $nav_items as $tab_key => $item ) : ?>
                <li class="nova-x-sidebar-item">
                    <a href="#" 
                       class="nova-x-sidebar-link <?php echo $current_tab === $tab_key ? 'active' : ''; ?>" 
                       data-tab="<?php echo esc_attr( $tab_key ); ?>"
                       aria-label="<?php echo esc_attr( $item['label'] ); ?>"
                       title="<?php echo esc_attr( $item['label'] ); ?>">
                        <span class="nova-x-sidebar-icon dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
                        <span class="nova-x-sidebar-label"><?php echo esc_html( $item['label'] ); ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>

