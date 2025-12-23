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
define( 'NOVA_X_PATH', plugin_dir_path( __FILE__ ) );
define( 'NOVA_X_URL', plugin_dir_url( __FILE__ ) );

/**
 * TEMPORARY: Test OpenAI API Connection from .env file
 * TODO: Remove this function after testing
 */
function nova_x_test_openai_connection() {
    // Only show on admin pages
    if ( ! is_admin() ) {
        return;
    }

    // Read .env file
    $env_file = NOVA_X_PATH . '.env';
    $api_key = '';
    
    if ( file_exists( $env_file ) ) {
        $env_content = file_get_contents( $env_file );
        $lines = explode( "\n", $env_content );
        
        foreach ( $lines as $line ) {
            $line = trim( $line );
            // Skip comments and empty lines
            if ( empty( $line ) || strpos( $line, '#' ) === 0 ) {
                continue;
            }
            // Check if this line contains OPENAI_API_KEY
            if ( strpos( $line, 'OPENAI_API_KEY' ) === 0 ) {
                $parts = explode( '=', $line, 2 );
                if ( count( $parts ) === 2 ) {
                    $api_key = trim( $parts[1] );
                    break;
                }
            }
        }
    }
    
    // If no API key found, show error
    if ( empty( $api_key ) ) {
        echo '<div class="notice notice-error"><p><strong>Nova-X OpenAI Test:</strong> ❌ Error - OPENAI_API_KEY not found in .env file</p></div>';
        return;
    }
    
    // Test API connection with a simple "Hello" message
    $api_url = 'https://api.openai.com/v1/chat/completions';
    $body = array(
        'model'       => 'gpt-3.5-turbo',
        'messages'    => array(
            array(
                'role'    => 'user',
                'content' => 'Hello'
            )
        ),
        'max_tokens'  => 10,
    );
    
    $args = array(
        'body'        => json_encode( $body ),
        'headers'     => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'timeout'     => 15,
    );
    
    $response = wp_remote_post( $api_url, $args );
    
    // Check for WP_Error
    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        echo '<div class="notice notice-error"><p><strong>Nova-X OpenAI Test:</strong> ❌ Error - ' . esc_html( $error_message ) . '</p></div>';
        return;
    }
    
    // Check response code
    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );
    $data = json_decode( $response_body, true );
    
    if ( $response_code === 200 && isset( $data['choices'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p><strong>Nova-X OpenAI Test:</strong> ✅ Success - API connection working! Response: ' . esc_html( $data['choices'][0]['message']['content'] ?? 'OK' ) . '</p></div>';
    } else {
        $error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown error (Code: ' . $response_code . ')';
        echo '<div class="notice notice-error"><p><strong>Nova-X OpenAI Test:</strong> ❌ Error - ' . esc_html( $error_msg ) . '</p></div>';
    }
}
add_action( 'admin_notices', 'nova_x_test_openai_connection' );

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
    }

    /**
     * Load the required files
     */
    private function includes() {
        // Load Classes (using the lowercase folder 'classes')
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-openai.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-rest.php';
        require_once NOVA_X_PATH . 'inc/classes/class-nova-x-generator.php';
        // Initialize REST API
        new Nova_X_REST();
    }

    private function hooks() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
    }

    public function register_admin_menu() {
        add_menu_page(
            'Nova-X',
            'Nova-X',
            'manage_options',
            'nova-x',
            [ $this, 'render_admin_page' ],
            'dashicons-superhero', // Cool icon
            2
        );
    }

    public function render_admin_page() {
        echo '<div id="nova-x-app-root"></div>';
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

            wp_enqueue_style(
                'nova-x-style',
                NOVA_X_URL . 'build/style-index.css',
                array(),
                time()
            );

            // Localize script to pass API key to frontend
            wp_localize_script(
                'nova-x-app',
                'novaXData',
                array(
                    'apiKey' => get_option( 'nova_x_api_key', '' ),
                )
            );
        }
    }
}

// Ignite the engine
Nova_X_Core::instance();