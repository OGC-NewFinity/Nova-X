<?php
/**
 * REST API Handler
 * Connects the React Frontend to the PHP Backend.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_REST {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes() {
        // Endpoint to Save API Key: /wp-json/nova-x/v1/save-key
        register_rest_route( 'nova-x/v1', '/save-key', [
            'methods'  => 'POST',
            'callback' => [ $this, 'handle_save_key' ],
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ]);

        // Endpoint to Chat: /wp-json/nova-x/v1/chat
        register_rest_route( 'nova-x/v1', '/chat', [
            'methods'  => 'POST',
            'callback' => [ $this, 'handle_chat' ],
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ]);
    }

    public function handle_save_key( $request ) {
        $key = sanitize_text_field( $request->get_param( 'api_key' ) );
        update_option( 'nova_x_api_key', $key );
        return rest_ensure_response( [ 'success' => true, 'message' => 'API Key Saved!' ] );
    }

    public function handle_chat( $request ) {
        $prompt = sanitize_text_field( $request->get_param( 'prompt' ) );
        
        // Load the AI Engine
        $ai = new Nova_X_OpenAI();
        
        // Prepare simple message context
        $messages = [
            [ 'role' => 'system', 'content' => 'You are a WordPress Theme Architect. You help build block themes.' ],
            [ 'role' => 'user', 'content' => $prompt ]
        ];

        $response = $ai->chat( $messages );

        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'ai_error', $response->get_error_message(), [ 'status' => 500 ] );
        }

        return rest_ensure_response( [ 'success' => true, 'reply' => $response ] );
    }
}