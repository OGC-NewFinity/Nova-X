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
        // Endpoint to Save API Key
        register_rest_route( 'nova-x/v1', '/save-key', [
            'methods'  => 'POST',
            'callback' => [ $this, 'handle_save_key' ],
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ]);

        // Endpoint to Chat
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
        
        // 1. Load the AI Engine
        $ai = new Nova_X_OpenAI();
        
        // 2. The "System Prompt" - This instructs the AI on HOW to build the theme
        $system_instruction = 'You are Nova-X, an expert WordPress Theme Architect.
        When the user asks to build a theme, you MUST return a strict JSON object.
        Do not include markdown formatting (like ```json). Just the raw JSON.
        
        The JSON structure must be:
        {
            "action": "build_theme",
            "slug": "nova-theme-v1",
            "theme_json": {
                "version": 2,
                "settings": {
                    "color": {
                        "palette": [ ... suggest 3 colors based on user prompt ... ]
                    }
                }
            },
            "index_html": "... generate a valid block theme homepage HTML ... ",
            "message": "I have drafted a new theme for you based on your requirements."
        }
        
        If the user is just saying hello or asking a question, return a normal JSON with just a "message" key.
        Example: { "message": "Hello! How can I help you build a site today?" }';

        $messages = [
            [ 'role' => 'system', 'content' => $system_instruction ],
            [ 'role' => 'user', 'content' => $prompt ]
        ];

        // 3. Get response from AI
        $raw_response = $ai->chat( $messages );

        if ( is_wp_error( $raw_response ) ) {
            return new WP_Error( 'ai_error', $raw_response->get_error_message(), [ 'status' => 500 ] );
        }

        // 4. Clean and Decode the JSON
        // Sometimes AI wraps code in ```json ... ```, we strip that.
        $clean_json = str_replace( ['```json', '```'], '', $raw_response );
        $data = json_decode( $clean_json, true );

        // If JSON is invalid, just return the raw text
        if ( json_last_error() !== JSON_ERROR_NONE ) {
             return rest_ensure_response( [ 'success' => true, 'reply' => $raw_response ] );
        }

        // 5. Check if we need to BUILD a theme
        if ( isset( $data['action'] ) && $data['action'] === 'build_theme' ) {
            
            // Call the Generator Class!
            $generator = new Nova_X_Generator();
            $build_result = $generator->build_theme( $data['slug'], $data );
            
            // Respond to the UI
            return rest_ensure_response( [ 
                'success' => true, 
                'reply'   => $data['message'] . " (Theme files created at: " . $build_result['path'] . ")"
            ] );
        }

        // Normal chat response
        return rest_ensure_response( [ 
            'success' => true, 
            'reply'   => $data['message'] ?? $raw_response
        ] );
    }
}