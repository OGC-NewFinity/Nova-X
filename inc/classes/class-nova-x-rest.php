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

        register_rest_route(
            'nova-x/v1',
            '/rotate-token',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_rotate_token' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/export-theme',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_export_theme' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/preview-theme',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_preview_theme' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/install-theme',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_install_theme' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/reset-usage-tracker',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_reset_usage_tracker' ],
                'permission_callback' => [ $this, 'check_permissions' ],
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

        // Load AI Engine to generate theme code
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-ai-engine.php';
        $ai_engine = new Nova_X_AI_Engine();
        $ai_result = $ai_engine->generate_theme_code( $prompt );

        // If AI generation failed, return error
        if ( ! $ai_result['success'] ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => $ai_result['message'] ?? 'Theme generation failed.',
                ],
                500
            );
        }

        // Load and instantiate the Architect class to create theme files
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-architect.php';
        $architect = new Nova_X_Architect();
        $result    = $architect->build_theme( $site_title, $prompt );

        // Add AI-generated code to response for export functionality
        if ( $result['success'] && isset( $ai_result['output'] ) ) {
            $result['output'] = $ai_result['output'];
            $result['provider'] = $ai_result['provider'] ?? 'unknown';
        }

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
     * Handle token rotation request
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function handle_rotate_token( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        // Verify nonce for CSRF protection
        if ( ! isset( $params['nonce'] ) || ! wp_verify_nonce( $params['nonce'], 'nova_x_nonce' ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid nonce. Please refresh the page and try again.',
                ],
                403
            );
        }

        // Sanitize and validate provider
        $provider = isset( $params['provider'] ) ? sanitize_text_field( $params['provider'] ) : '';
        
        if ( empty( $provider ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Provider parameter is required.',
                ],
                400
            );
        }

        // Validate provider is in allowed list
        $allowed_providers = [ 'openai', 'anthropic', 'groq', 'mistral', 'gemini', 'claude', 'cohere' ];
        if ( ! in_array( $provider, $allowed_providers, true ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid provider specified.',
                ],
                400
            );
        }

        // Get force parameter (default: false)
        $force = isset( $params['force'] ) ? (bool) $params['force'] : false;

        // Load Token Manager
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-token-manager.php';

        // Get new API key from request (required for rotation)
        $new_key = isset( $params['new_key'] ) ? trim( sanitize_text_field( $params['new_key'] ) ) : '';

        if ( empty( $new_key ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'API key is required for rotation. Please enter a key in the API Key field first.',
                ],
                400
            );
        }

        // Attempt to rotate the key
        $result = Nova_X_Token_Manager::rotate_key( $provider, $new_key, $force );

        if ( $result ) {
            return new WP_REST_Response(
                [
                    'success' => true,
                    'message' => 'Token rotated successfully for ' . esc_html( $provider ) . '.',
                ],
                200
            );
        } else {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Failed to rotate token. Please check that the new key is valid.',
                ],
                500
            );
        }
    }

    /**
     * Handle theme export request
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function handle_export_theme( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        // Verify nonce for CSRF protection
        if ( ! isset( $params['nonce'] ) || ! wp_verify_nonce( $params['nonce'], 'nova_x_nonce' ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid nonce. Please refresh the page and try again.',
                ],
                403
            );
        }

        // Sanitize and validate input parameters
        $site_title = isset( $params['site_title'] ) ? sanitize_text_field( $params['site_title'] ) : '';
        $code       = isset( $params['code'] ) ? wp_kses_post( $params['code'] ) : '';

        // Validate required fields
        if ( empty( $site_title ) || empty( $code ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Site title and code are required for export.',
                ],
                400
            );
        }

        // Load Theme Exporter
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-theme-exporter.php';

        // Export theme
        $result = Nova_X_Theme_Exporter::export_theme( $site_title, $code );

        if ( $result['success'] ) {
            return new WP_REST_Response(
                [
                    'success'      => true,
                    'download_url' => esc_url_raw( $result['download_url'] ),
                    'filename'     => esc_html( $result['filename'] ),
                    'message'      => $result['message'],
                ],
                200
            );
        } else {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => $result['message'],
                ],
                500
            );
        }
    }

    /**
     * Handle theme preview request
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function handle_preview_theme( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        // Verify nonce for CSRF protection
        if ( ! isset( $params['nonce'] ) || ! wp_verify_nonce( $params['nonce'], 'nova_x_nonce' ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid nonce. Please refresh the page and try again.',
                ],
                403
            );
        }

        // Sanitize and validate input
        $zip_url = isset( $params['zip_url'] ) ? esc_url_raw( $params['zip_url'] ) : '';

        if ( empty( $zip_url ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'ZIP URL is required for preview.',
                ],
                400
            );
        }

        // Load Theme Installer
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-theme-installer.php';

        // Preview theme
        $result = Nova_X_Theme_Installer::preview_theme( $zip_url );

        if ( $result['success'] ) {
            return new WP_REST_Response(
                [
                    'success'     => true,
                    'preview_url' => esc_url_raw( $result['preview_url'] ),
                    'theme_slug'  => esc_html( $result['theme_slug'] ),
                    'message'     => $result['message'],
                ],
                200
            );
        } else {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => $result['message'],
                ],
                500
            );
        }
    }

    /**
     * Handle theme installation request
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function handle_install_theme( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        // Verify nonce for CSRF protection
        if ( ! isset( $params['nonce'] ) || ! wp_verify_nonce( $params['nonce'], 'nova_x_nonce' ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid nonce. Please refresh the page and try again.',
                ],
                403
            );
        }

        // Sanitize and validate input
        $zip_url = isset( $params['zip_url'] ) ? esc_url_raw( $params['zip_url'] ) : '';

        if ( empty( $zip_url ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'ZIP URL is required for installation.',
                ],
                400
            );
        }

        // Load Theme Installer
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-theme-installer.php';

        // Install theme
        $result = Nova_X_Theme_Installer::install_theme( $zip_url );

        if ( $result['success'] ) {
            return new WP_REST_Response(
                [
                    'success'     => true,
                    'theme_slug'  => esc_html( $result['theme_slug'] ),
                    'theme_name'  => esc_html( $result['theme_name'] ),
                    'message'     => $result['message'],
                ],
                200
            );
        } else {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => $result['message'],
                ],
                500
            );
        }
    }

    /**
     * Handle reset usage tracker request
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function handle_reset_usage_tracker( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        // Verify nonce for CSRF protection
        if ( ! isset( $params['nonce'] ) || ! wp_verify_nonce( $params['nonce'], 'nova_x_nonce' ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid nonce. Please refresh the page and try again.',
                ],
                403
            );
        }

        // Load Usage Tracker
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-usage-tracker.php';

        // Reset tracker
        $result = Nova_X_Usage_Tracker::reset_tracker();

        if ( $result ) {
            return new WP_REST_Response(
                [
                    'success' => true,
                    'message' => 'Usage tracker reset successfully.',
                ],
                200
            );
        } else {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Failed to reset usage tracker.',
                ],
                500
            );
        }
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
