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
            '/validate-key',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'validate_api_key' ],
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
                'permission_callback' => function () {
                    // Check for install_themes capability specifically for theme installation
                    return current_user_can( 'install_themes' );
                },
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/get-usage-stats',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'handle_get_usage_stats' ],
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

        register_rest_route(
            'nova-x/v1',
            '/list-exported-themes',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'handle_list_exported_themes' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/delete-exported-theme',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_delete_exported_theme' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/reexport-theme',
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_reexport_theme' ],
                'permission_callback' => [ $this, 'check_permissions' ],
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/output-files',
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_output_files' ],
                'permission_callback' => '__return_true', // Later secure with nonce/cap
            ]
        );

        register_rest_route(
            'nova-x/v1',
            '/theme-preference',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'handle_get_theme_preference' ],
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    },
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'handle_update_theme_preference' ],
                    'permission_callback' => function () {
                        return is_user_logged_in();
                    },
                ],
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

        // Get provider from request or default to openai
        $provider = sanitize_key( $request->get_param( 'provider' ) );
        if ( empty( $provider ) ) {
            // Try to detect provider from key format
            if ( ! class_exists( 'Nova_X_Provider_Rules' ) ) {
                require_once NOVA_X_PATH . 'inc/classes/class-nova-x-provider-rules.php';
            }
            $detected_provider = Nova_X_Provider_Rules::detect_provider_from_key( $key );
            $provider = $detected_provider ? $detected_provider : 'openai';
        }

        // Prevent saving masked placeholders
        if ( strpos( $key, '*' ) !== false ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Masked API key cannot be saved.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Masked API key cannot be saved.',
                    ],
                ],
                400
            );
        }

        // Empty key check
        if ( empty( $key ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'API key cannot be empty.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'API key cannot be empty.',
                    ],
                ],
                400
            );
        }

        // Validate API key format using Provider Rules
        if ( ! class_exists( 'Nova_X_Provider_Rules' ) ) {
            require_once NOVA_X_PATH . 'inc/classes/class-nova-x-provider-rules.php';
        }

        $validation = Nova_X_Provider_Rules::validate_key( $key, $provider );
        
        if ( ! $validation['valid'] ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => $validation['message'],
                    'notifier' => [
                        'type' => 'error',
                        'message' => $validation['message'],
                    ],
                ],
                400
            );
        }

        // Check if key has changed
        $existing_key = Nova_X_Token_Manager::get_decrypted_key( $provider );
        if ( $existing_key === $key ) {
            return new WP_REST_Response(
                [
                    'success' => true,
                    'message' => 'API key unchanged.',
                    'notifier' => [
                        'type' => 'info',
                        'message' => 'API key unchanged.',
                    ],
                ],
                200
            );
        }

        // Encrypt and store the key
        $encrypted = Nova_X_Token_Manager::store_encrypted_key( $provider, $key );
        
        if ( ! $encrypted ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Failed to encrypt and save API key.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Failed to encrypt and save API key.',
                    ],
                ],
                500
            );
        }

        return new WP_REST_Response(
            [
                'success' => true,
                'message' => 'API key saved successfully.',
                'notifier' => [
                    'type' => 'success',
                    'message' => 'API key saved successfully.',
                ],
            ],
            200
        );
    }

    /**
     * Validate API key format against provider rules (without saving)
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response Response object.
     */
    public function validate_api_key( WP_REST_Request $request ) {
        // Load Token Manager if not already loaded
        if ( ! class_exists( 'Nova_X_Token_Manager' ) ) {
            require_once NOVA_X_PATH . 'inc/classes/class-nova-x-token-manager.php';
        }

        $raw_key = (string) $request->get_param( 'api_key' );
        $api_key = trim( sanitize_text_field( $raw_key ) );

        // Get provider from request
        $provider = sanitize_key( $request->get_param( 'provider' ) );
        if ( empty( $provider ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'status'  => 'invalid',
                    'message' => 'Provider is required.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Provider is required.',
                    ],
                ],
                400
            );
        }

        // Validate the API key using Token Manager
        $validation = Nova_X_Token_Manager::validate_api_key( $provider, $api_key );

        // Prepare response
        $response_data = [
            'success'          => $validation['valid'],
            'status'           => $validation['status'],
            'message'          => $validation['message'],
            'provider_label'   => isset( $validation['provider_label'] ) ? $validation['provider_label'] : ucfirst( $provider ),
            'notifier' => [
                'type'    => $validation['valid'] ? 'success' : 'error',
                'message' => $validation['message'],
            ],
        ];

        // Add debug info if available
        if ( isset( $validation['rule'] ) ) {
            $response_data['rule'] = $validation['rule'];
        }
        if ( isset( $validation['provider'] ) ) {
            $response_data['provider'] = $validation['provider'];
        }

        $status_code = $validation['valid'] ? 200 : 400;

        return new WP_REST_Response( $response_data, $status_code );
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
            error_log( '[Nova-X] REST API security - Invalid nonce for generate-theme endpoint — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'error' => 'Invalid nonce',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Invalid security token. Please refresh the page and try again.',
                    ],
                ],
                403
            );
        }

        // Sanitize and validate input parameters.
        $site_title = nova_x_sanitize_text( $params['title'] ?? '' );
        $prompt     = nova_x_sanitize_prompt( $params['prompt'] ?? '' );

        // Validate required fields.
        if ( empty( $site_title ) || empty( $prompt ) ) {
            error_log( '[Nova-X] Theme generation failed - Missing required parameters (title or prompt) — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'error' => 'Missing title or prompt',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Please provide both a theme title and prompt.',
                    ],
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
            $error_message = $ai_result['message'] ?? 'Theme generation failed.';
            $provider = $ai_result['provider'] ?? 'unknown';
            error_log( '[Nova-X] Theme generation failed - AI engine error for theme "' . $site_title . '" using provider ' . $provider . ' — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => $error_message,
                    'notifier' => [
                        'type' => 'error',
                        'message' => $error_message,
                    ],
                ],
                500
            );
        }

        // Load and instantiate the Generator class to create theme files
        // Note: Generator is already loaded in nova-x.php, but we ensure it's available here
        $generator = new Nova_X_Generator();
        $result    = $generator->build_theme( $site_title, $prompt );

        // Log failure if theme building failed
        if ( ! $result['success'] ) {
            $slug = sanitize_title( $site_title );
            error_log( '[Nova-X] Theme building failed - Generator error for theme "' . $site_title . '" (slug: ' . $slug . ') — User ID: ' . get_current_user_id() );
        }

        // Add AI-generated code to response for export functionality
        if ( $result['success'] && isset( $ai_result['output'] ) ) {
            $result['output'] = $ai_result['output'];
            $result['provider'] = $ai_result['provider'] ?? 'unknown';
            $result['notifier'] = [
                'type' => 'success',
                'message' => $result['message'] ?? 'Theme generated successfully!',
            ];
        } else {
            $result['notifier'] = [
                'type' => 'error',
                'message' => $result['message'] ?? 'Theme generation failed.',
            ];
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
                'success' => true,
                'themes' => $themes,
                'notifier' => [
                    'type' => 'info',
                    'message' => 'Themes list loaded successfully.',
                ],
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
        // Check permissions first
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Permission denied. You do not have sufficient permissions.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Permission denied. You do not have sufficient permissions.',
                    ],
                ],
                403
            );
        }

        // Verify nonce for CSRF protection - check header first, then params
        $nonce_header = $request->get_header( 'X-WP-Nonce' );
        $params = $request->get_json_params();
        $nonce = ! empty( $nonce_header ) ? $nonce_header : ( isset( $params['nonce'] ) ? $params['nonce'] : '' );

        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'nova_x_nonce' ) ) {
            error_log( '[Nova-X] REST API security - Invalid nonce for rotate-token endpoint — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid security token. Please refresh the page and try again.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Invalid security token. Please refresh the page and try again.',
                    ],
                ],
                403
            );
        }

        // Sanitize and validate provider
        $provider = isset( $params['provider'] ) ? sanitize_text_field( $params['provider'] ) : '';
        
        if ( empty( $provider ) ) {
            error_log( '[Nova-X] Token rotation failed - Provider parameter missing — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Provider parameter is required.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Provider parameter is required.',
                    ],
                ],
                400
            );
        }

        // Validate provider using Provider Manager
        if ( ! class_exists( 'Nova_X_Provider_Manager' ) ) {
            require_once NOVA_X_PATH . 'inc/classes/class-nova-x-provider-manager.php';
        }
        
        if ( ! Nova_X_Provider_Manager::is_valid_provider( $provider ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid provider specified.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Invalid provider specified.',
                    ],
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
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'API key is required for rotation. Please enter a key in the API Key field first.',
                    ],
                ],
                400
            );
        }

        // Check if key is masked (contains bullet points or asterisks)
        if ( strpos( $new_key, '•' ) !== false || strpos( $new_key, '*' ) !== false ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Masked API key cannot be used for rotation. Please enter a valid, unmasked key.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Masked API key cannot be used for rotation. Please enter a valid, unmasked key.',
                    ],
                ],
                400
            );
        }

        // Validate key format using Provider Rules
        if ( ! class_exists( 'Nova_X_Provider_Rules' ) ) {
            require_once NOVA_X_PATH . 'inc/classes/class-nova-x-provider-rules.php';
        }

        $validation = Nova_X_Provider_Rules::validate_key( $new_key, $provider );
        
        if ( ! $validation['valid'] ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => $validation['message'],
                    'notifier' => [
                        'type' => 'error',
                        'message' => $validation['message'],
                    ],
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
                    'notifier' => [
                        'type' => 'success',
                        'message' => 'Token rotated successfully for ' . esc_html( $provider ) . '.',
                    ],
                ],
                200
            );
        } else {
            error_log( '[Nova-X] Token rotation failed - Provider: ' . $provider . ' — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Failed to rotate token. Please check that the new key is valid.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Failed to rotate token. Please check that the new key is valid.',
                    ],
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
            error_log( '[Nova-X] REST API security - Invalid nonce for export-theme endpoint — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid nonce. Please refresh the page and try again.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Invalid security token. Please refresh the page and try again.',
                    ],
                ],
                403
            );
        }

        // Sanitize and validate input parameters
        $site_title = isset( $params['site_title'] ) ? sanitize_text_field( $params['site_title'] ) : '';
        $code       = isset( $params['code'] ) ? wp_kses_post( $params['code'] ) : '';

        // Validate required fields
        if ( empty( $site_title ) || empty( $code ) ) {
            error_log( '[Nova-X] Theme export failed - Missing required parameters (site_title or code) — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Site title and code are required for export.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Site title and code are required for export.',
                    ],
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
                    'notifier' => [
                        'type' => 'success',
                        'message' => $result['message'],
                    ],
                ],
                200
            );
        } else {
            error_log( '[Nova-X] Theme export failed - ' . ( $result['message'] ?? 'Unknown error' ) . ' — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => $result['message'],
                    'notifier' => [
                        'type' => 'error',
                        'message' => $result['message'],
                    ],
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
            error_log( '[Nova-X] REST API security - Invalid nonce for preview-theme endpoint — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid nonce. Please refresh the page and try again.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Invalid security token. Please refresh the page and try again.',
                    ],
                ],
                403
            );
        }

        // Sanitize and validate input
        $zip_url = isset( $params['zip_url'] ) ? esc_url_raw( $params['zip_url'] ) : '';

        if ( empty( $zip_url ) ) {
            error_log( '[Nova-X] Theme preview failed - ZIP URL missing — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'ZIP URL is required for preview.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'ZIP URL is required for preview.',
                    ],
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
                    'notifier' => [
                        'type' => 'success',
                        'message' => $result['message'],
                    ],
                ],
                200
            );
        } else {
            error_log( '[Nova-X] Theme preview failed - ' . ( $result['message'] ?? 'Unknown error' ) . ' — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => $result['message'],
                    'notifier' => [
                        'type' => 'error',
                        'message' => $result['message'],
                    ],
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

        // Verify user capability (additional check beyond permission_callback)
        if ( ! current_user_can( 'install_themes' ) ) {
            error_log( '[Nova-X] REST API security - User lacks install_themes capability for install-theme endpoint — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'You do not have permission to install themes.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'You do not have permission to install themes.',
                    ],
                ],
                403
            );
        }

        // Verify nonce for CSRF protection
        if ( ! isset( $params['nonce'] ) || ! wp_verify_nonce( $params['nonce'], 'nova_x_nonce' ) ) {
            error_log( '[Nova-X] REST API security - Invalid nonce for install-theme endpoint — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid nonce. Please refresh the page and try again.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Invalid security token. Please refresh the page and try again.',
                    ],
                ],
                403
            );
        }

        // Sanitize and validate input
        $zip_url = isset( $params['zip_url'] ) ? esc_url_raw( $params['zip_url'] ) : '';

        if ( empty( $zip_url ) ) {
            error_log( '[Nova-X] Theme installation failed - ZIP URL missing — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'ZIP URL is required for installation.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'ZIP URL is required for installation.',
                    ],
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
                    'notifier' => [
                        'type' => 'success',
                        'message' => $result['message'],
                    ],
                ],
                200
            );
        } else {
            error_log( '[Nova-X] Theme installation failed - ' . ( $result['message'] ?? 'Unknown error' ) . ' — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => $result['message'],
                    'notifier' => [
                        'type' => 'error',
                        'message' => $result['message'],
                    ],
                ],
                500
            );
        }
    }

    /**
     * Handle get usage stats request
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function handle_get_usage_stats( WP_REST_Request $request ) {
        // Load Usage Tracker
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-usage-tracker.php';

        // Get total stats
        $total_tokens = Nova_X_Usage_Tracker::get_total_tokens();
        $total_cost = Nova_X_Usage_Tracker::get_total_cost();

        // Get provider data
        $provider_data = Nova_X_Usage_Tracker::get_all_provider_data();

        // Calculate percentages for each provider
        $provider_stats = [];
        foreach ( $provider_data as $provider => $data ) {
            $token_percent = $total_tokens > 0 
                ? round( ( $data['tokens'] / $total_tokens ) * 100, 1 ) 
                : 0;

            $provider_stats[] = [
                'provider' => esc_html( $data['label'] ),
                'tokens' => absint( $data['tokens'] ),
                'cost' => round( floatval( $data['cost'] ), 4 ),
                'percentage' => $token_percent,
            ];
        }

        return rest_ensure_response( [
            'success' => true,
            'total_tokens' => absint( $total_tokens ),
            'total_cost' => round( floatval( $total_cost ), 4 ),
            'providers' => $provider_stats,
            'notifier' => [
                'type' => 'info',
                'message' => 'Usage statistics loaded successfully.',
            ],
        ] );
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
            error_log( '[Nova-X] REST API security - Invalid nonce for reset-usage-tracker endpoint — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Invalid nonce. Please refresh the page and try again.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Invalid security token. Please refresh the page and try again.',
                    ],
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
                    'notifier' => [
                        'type' => 'success',
                        'message' => 'Usage tracker reset successfully.',
                    ],
                ],
                200
            );
        } else {
            error_log( '[Nova-X] Usage tracker reset failed — User ID: ' . get_current_user_id() );
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Failed to reset usage tracker.',
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Failed to reset usage tracker.',
                    ],
                ],
                500
            );
        }
    }

    /**
     * Handle list exported themes request
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function handle_list_exported_themes( WP_REST_Request $request ) {
        // Load Theme Manager
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-theme-manager.php';

        $themes = Nova_X_Theme_Manager::list_exported_themes();

        return rest_ensure_response( [
            'success' => true,
            'themes'  => $themes,
            'notifier' => [
                'type' => 'info',
                'message' => 'Exported themes loaded successfully.',
            ],
        ] );
    }

    /**
     * Handle delete exported theme request
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function handle_delete_exported_theme( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        // Verify nonce for CSRF protection
        if ( ! isset( $params['nonce'] ) || ! wp_verify_nonce( $params['nonce'], 'nova_x_nonce' ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Invalid nonce. Please refresh the page and try again.',
            ], 403 );
        }

        // Sanitize and validate slug
        $slug = isset( $params['slug'] ) ? sanitize_file_name( $params['slug'] ) : '';

        if ( empty( $slug ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Theme slug is required.',
                'notifier' => [
                    'type' => 'error',
                    'message' => 'Theme slug is required.',
                ],
            ], 400 );
        }

        // Load Theme Manager
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-theme-manager.php';

        $result = Nova_X_Theme_Manager::delete_exported_theme( $slug );

        if ( $result['success'] ) {
            return new WP_REST_Response( [
                'success' => true,
                'message' => $result['message'],
                'notifier' => [
                    'type' => 'success',
                    'message' => $result['message'],
                ],
            ], 200 );
        } else {
            error_log( '[Nova-X] Delete exported theme failed - Slug: ' . $slug . ' - ' . ( $result['message'] ?? 'Unknown error' ) . ' — User ID: ' . get_current_user_id() );
            return new WP_REST_Response( [
                'success' => false,
                'message' => $result['message'],
                'notifier' => [
                    'type' => 'error',
                    'message' => $result['message'],
                ],
            ], 500 );
        }
    }

    /**
     * Handle reexport theme request
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function handle_reexport_theme( WP_REST_Request $request ) {
        $params = $request->get_json_params();

        // Verify nonce for CSRF protection
        if ( ! isset( $params['nonce'] ) || ! wp_verify_nonce( $params['nonce'], 'nova_x_nonce' ) ) {
            error_log( '[Nova-X] REST API security - Invalid nonce for reexport-theme endpoint — User ID: ' . get_current_user_id() );
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Invalid nonce. Please refresh the page and try again.',
            ], 403 );
        }

        // Sanitize and validate slug
        $slug = isset( $params['slug'] ) ? sanitize_file_name( $params['slug'] ) : '';

        if ( empty( $slug ) ) {
            error_log( '[Nova-X] Re-export theme failed - Theme slug missing — User ID: ' . get_current_user_id() );
            return new WP_REST_Response( [
                'success' => false,
                'message' => 'Theme slug is required.',
                'notifier' => [
                    'type' => 'error',
                    'message' => 'Theme slug is required.',
                ],
            ], 400 );
        }

        // Load Theme Manager
        require_once plugin_dir_path( __FILE__ ) . 'class-nova-x-theme-manager.php';

        $result = Nova_X_Theme_Manager::reexport_theme( $slug );

        if ( $result['success'] ) {
            return new WP_REST_Response( [
                'success'      => true,
                'message'      => $result['message'],
                'download_url' => esc_url_raw( $result['download_url'] ),
                'filename'     => esc_html( $result['filename'] ),
                'notifier' => [
                    'type' => 'success',
                    'message' => $result['message'],
                ],
            ], 200 );
        } else {
            error_log( '[Nova-X] Re-export theme failed - Slug: ' . $slug . ' - ' . ( $result['message'] ?? 'Unknown error' ) . ' — User ID: ' . get_current_user_id() );
            return new WP_REST_Response( [
                'success' => false,
                'message' => $result['message'],
                'notifier' => [
                    'type' => 'error',
                    'message' => $result['message'],
                ],
            ], 500 );
        }
    }

    /**
     * Get output files from the latest generated theme
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response|WP_Error Response object.
     */
    public function get_output_files( WP_REST_Request $request ) {
        // Determine the path to the latest generated theme folder
        $themes_dir = WP_CONTENT_DIR . '/themes/';
        
        // Get all theme directories
        $theme_folders = array_filter( glob( trailingslashit( $themes_dir ) . '*' ), 'is_dir' );
        
        if ( empty( $theme_folders ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Theme output not found.',
                    ],
                ],
                404
            );
        }
        
        // Find the most recently modified theme directory that contains required files
        $latest_theme_dir = null;
        $latest_mtime = 0;
        
        foreach ( $theme_folders as $theme_folder ) {
            // Check if required files exist
            $style_css = trailingslashit( $theme_folder ) . 'style.css';
            $functions_php = trailingslashit( $theme_folder ) . 'functions.php';
            $index_php = trailingslashit( $theme_folder ) . 'index.php';
            
            if ( file_exists( $style_css ) && file_exists( $functions_php ) && file_exists( $index_php ) ) {
                // Get the modification time of the directory
                $mtime = filemtime( $theme_folder );
                if ( $mtime > $latest_mtime ) {
                    $latest_mtime = $mtime;
                    $latest_theme_dir = $theme_folder;
                }
            }
        }
        
        // If no valid theme directory found, return error
        if ( ! $latest_theme_dir || ! is_dir( $latest_theme_dir ) ) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Theme output not found.',
                    ],
                ],
                404
            );
        }
        
        // Load each file
        $files = [ 'style.css', 'functions.php', 'index.php' ];
        $data = [];
        
        foreach ( $files as $file ) {
            $path = trailingslashit( $latest_theme_dir ) . $file;
            $data[ $file ] = file_exists( $path ) ? file_get_contents( $path ) : '';
        }
        
        return rest_ensure_response(
            [
                'success' => true,
                'files' => $data,
                'notifier' => [
                    'type' => 'success',
                    'message' => 'Theme files loaded successfully.',
                ],
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

    /**
     * Handle get theme preference request
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response Response object with theme preference.
     */
    public function handle_get_theme_preference( $request ) {
        $theme = get_user_meta( get_current_user_id(), 'nova_x_theme', true ) ?: 'dark';
        return new WP_REST_Response( [ 'theme' => $theme ], 200 );
    }

    /**
     * Handle update theme preference request
     *
     * @param WP_REST_Request $request REST request object.
     * @return WP_REST_Response Response object with updated theme preference.
     */
    public function handle_update_theme_preference( $request ) {
        $theme = sanitize_text_field( $request->get_param( 'theme' ) );
        if ( ! in_array( $theme, [ 'dark', 'light' ], true ) ) {
            return new WP_REST_Response(
                [
                    'notifier' => [
                        'type' => 'error',
                        'message' => 'Invalid theme value.',
                    ],
                ],
                400
            );
        }

        update_user_meta( get_current_user_id(), 'nova_x_theme', $theme );
        return new WP_REST_Response(
            [
                'theme' => $theme,
                'notifier' => [
                    'type' => 'success',
                    'message' => 'Theme preference saved.',
                ],
            ],
            200
        );
    }
}
