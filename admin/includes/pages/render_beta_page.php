<?php
/**
 * Beta Tools Page Render
 * 
 * @package Nova-X
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure Nova_X_Notifier class is loaded
if ( ! class_exists( 'Nova_X_Notifier' ) ) {
    $notifier_path = NOVA_X_PATH . 'inc/classes/class-nova-x-notifier.php';
    if ( file_exists( $notifier_path ) ) {
        require_once $notifier_path;
    }
}

nova_x_wrap_header( 'Beta Tools', [
    'subtitle' => 'Experimental Features & AI Template Utilities'
]);
?>
  <div class="nova-x-section">
    <?php echo Nova_X_Notifier::warning( '<strong>' . esc_html__( 'Warning:', 'nova-x' ) . '</strong> ' . esc_html__( 'These are beta features and may be unstable. Use at your own risk.', 'nova-x' ) ); ?>
    
    <h2>🧪 Experimental Features</h2>
    <p class="nova-x-muted">Use with caution. These tools are in testing and may change or be removed at any time.</p>

    <div class="nova-x-cards-grid">
      <div class="nova-x-card disabled">
        <h3>📜 Prompt Presets</h3>
        <p>Test AI-generated prompt templates for theme generation, code, and copywriting tools.</p>
        <span class="nova-x-badge beta">Beta</span>
      </div>

      <div class="nova-x-card disabled">
        <h3>🧩 Feature Toggles</h3>
        <p>Enable or disable experimental modules that affect frontend generation behavior.</p>
        <span class="nova-x-badge beta">Coming Soon</span>
      </div>
    </div>
  </div>
<?php
nova_x_wrap_footer();

