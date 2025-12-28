<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_REST {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {

        register_rest_route(
            'nova-x/v1',
            '/save-key',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'save_api_key' ],
                'permission_callback' => function () {
                    return current_user_can( 'manage_options' );
                },
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/generate-theme',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_generate_theme' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/list-themes',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'handle_list_themes' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/ping',
            [
                'methods'             => 'GET',
                'callback'            => fn() => rest_ensure_response( [ 'pong' => true ] ),
                'permission_callback' => '__return_true',
            ]
        );

        // IMPORTANT:
        // No automatic OpenAI test route
        // No admin notices
        // No background validation
    }

    public function save_api_key( WP_REST_Request $request ) {

        $raw_key = (string) $request->get_param( 'api_key' );
        $key     = trim( sanitize_text_field( $raw_key ) );

        // Prevent saving masked placeholders
        if ( strpos( $key, '*' ) !== false ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Masked API key cannot be saved.',
                ],
                400
            );
        }

        // Basic sanity check only
        if ( empty( $key ) || strpos( $key, 'sk-' ) !== 0 ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid API key format.',
                ],
                400
            );
        }

        update_option( 'nova_x_api_key', $key, false );

        return new WP_REST_Response(
            [
                'success' => true,
                'message' => 'API key saved successfully.',
            ],
            200
        );
    }

    /**
     * Handle theme generation request
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function handle_generate_theme( $request ) {
        $params = $request->get_json_params();

        // Verify nonce for CSRF protection.
        if ( ! isset( $params['nonce'] ) || ! wp_verify_nonce( $params['nonce'], 'nova_x_nonce' ) ) {
            return new WP_REST_Response(
                [
                    'error' => 'Invalid nonce',
                ],
                403
            );
        }

        // Sanitize and validate input parameters.
        $site_title = nova_x_sanitize_text( $params['title'] ?? '' );
        $prompt     = nova_x_sanitize_prompt( $params['prompt'] ?? '' );

        // Validate required fields.
        if ( empty( $site_title ) || empty( $prompt ) ) {
            return new WP_REST_Response(
                [
                    'error' => 'Missing title or prompt',
                ],
                400
            );
        }

        // Load and instantiate the Architect class.
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-architect.php';

        $architect = new Nova_X_Architect();
        $result    = $architect->build_theme( $site_title, $prompt );

        return rest_ensure_response( $result );
    }

    /**
     * Handle list themes request
     *
     * @return WP_REST_Response Response object with themes list.
     */
    public function handle_list_themes() {
        $themes_dir = WP_CONTENT_DIR . '/themes/';
        $folders    = array_filter( glob( $themes_dir . '*' ), 'is_dir' );

        $themes = array_map(
            function( $path ) {
                return basename( $path );
            },
            $folders
        );

        return rest_ensure_response(
            [
                'themes' => $themes,
            ]
        );
    }

    /**
     * Check user permissions for REST API access
     *
     * @return bool True if user has manage_options capability.
     */
    public function check_permissions() {
        return current_user_can( 'manage_options' );
    }
}
