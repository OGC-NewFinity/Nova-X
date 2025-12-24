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
}
