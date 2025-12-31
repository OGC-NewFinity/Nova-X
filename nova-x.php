<?php
/**
 * Plugin Name: Nova-X | AI Theme Architect
 * Description: The next-gen AI agent for building WordPress Block Themes via visual chat interface.
 * Version: 0.1.0
 * Author: OGC NewFinity
 * Author URI: https://ogcnewfinity.com
 * Text Domain: nova-x
 * Requires at least: 6.2
 * Requires PHP: 8.0
 */
// Connection Test
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Constants
define( 'NOVA_X_VERSION', '0.1.0' );
define( 'NOVA_X_PLUGIN_FILE', __FILE__ );
define( 'NOVA_X_PATH', plugin_dir_path( __FILE__ ) );
define( 'NOVA_X_URL', plugin_dir_url( __FILE__ ) );
define( 'NOVA_X_SUPPORTED_PROVIDERS', [ 'openai', 'anthropic', 'groq', 'mistral', 'gemini', 'claude', 'cohere' ] );

// Development Mode Toggle
// Set to false to hide dev-only features (Architecture, Beta Tools) in production
if ( ! defined( 'NOVA_X_DEV_MODE' ) ) {
    define( 'NOVA_X_DEV_MODE', true ); // Change to false on release
}

// Master encryption key for API key storage (never expose to frontend)
// Generate a secure random key: openssl rand -hex 32
if ( ! defined( 'NOVA_X_ENCRYPTION_KEY' ) ) {
    define( 'NOVA_X_ENCRYPTION_KEY', 'CHANGE_THIS_TO_A_SECURE_RANDOM_KEY_' . AUTH_SALT . SECURE_AUTH_SALT );
}

/**
 * Main Class Initialization
 */
final class Nova_X_Core {
    
    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Load Core Components
        $this->includes();
        $this->hooks();
        
        // Initialize Admin class if in admin area
        if ( is_admin() ) {
            new Nova_X_Admin( NOVA_X_VERSION );
            // Note: Nova_X_Settings is now instantiated within Nova_X_Admin constructor
        }
    }

    /**
     * Load the required files
     */
    private function includes() {
        // Load helper functions first
        require_once NOVA_X_PATH . 'inc/helpers/helper-functions.php';
        
        // Load Classes (using the lowercase folder 'classes')
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-session.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-token-manager.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-provider-rules.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-provider-manager.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-provider-factory.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-ai-engine.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-rest.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-generator.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-theme-exporter.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-theme-installer.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-usage-tracker.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-notifier.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-admin.php';
        
        // Load Authentication class
        require_once NOVA_X_PATH . 'admin/class-nova-x-auth.php';
        
        // Load Account class
        require_once NOVA_X_PATH . 'admin/class-nova-x-account.php';
        
        // Initialize REST API
        new Nova_X_REST();
        
        // Initialize Authentication
        new Nova_X_Auth();
    }

    private function hooks() {
        // Menu registration is now handled in Nova_X_Admin class
        // Script enqueuing is also handled in Nova_X_Admin class
        
        // Enable session support for authentication
        add_action( 'init', function() {
            Nova_X_Session::start();
        });
    }

    public function enqueue_admin_assets( $hook ) {
        // Only load on our plugin page
        if ( 'toplevel_page_nova-x' !== $hook ) {
            return;
        }

        // Load the React App assets from the 'build' folder
        // Note: npm run start/build creates the 'build' folder automatically
        $asset_file_path = NOVA_X_PATH . 'build/index.asset.php';
        
        if ( file_exists( $asset_file_path ) ) {
            $asset_file = include( $asset_file_path );

            wp_enqueue_script(
                'nova-x-app',
                NOVA_X_URL . 'build/index.js',
                $asset_file['dependencies'],
                time(),
                true
            );
            $stored_key = get_option('nova_x_api_key', '');
            $has_key = ! empty( trim( $stored_key ) );
            
            wp_localize_script(
                'nova-x-app',
                'novaXData',
                array(
                    'hasKey'    => $has_key,
                    'maskedKey' => $has_key ? '********' : '',
                )
            );            
            
            wp_enqueue_style(
                'nova-x-style',
                NOVA_X_URL . 'build/style-index.css',
                array(),
                time()
            );


        }
    }

}

// Ignite the engine
Nova_X_Core::instance();

/**
 * Load admin assets (logo styles)
 */
function nova_x_load_assets() {
    // Load CSS globally (fixes logo not appearing)
    wp_enqueue_style(
        'nova-x-admin-style',
        plugin_dir_url( __FILE__ ) . 'assets/css/admin.css'
    );
}
add_action( 'admin_enqueue_scripts', 'nova_x_load_assets' );