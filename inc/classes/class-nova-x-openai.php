<?php
/**
 * OpenAI Connector Class
 * Handles direct communication with the OpenAI API.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_OpenAI {

    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    private $model   = 'gpt-4o'; // or 'gpt-3.5-turbo' for testing

    public function __construct() {
        // Use NOVA_X_API_KEY constant directly
        $this->api_key = NOVA_X_API_KEY;
    }

    /**
     * Send a prompt to the AI and get a response.
     * * @param array $messages The chat history (Context).
     * @return array|WP_Error Response or Error.
     */
    public function chat( $messages ) {
        // API key is always available from NOVA_X_API_KEY constant - no check needed

        $body = [
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => 0.7, // Balance between creativity and precision
        ];

        // Log API key for debugging
        error_log( 'NOVA_X_API_KEY value: ' . NOVA_X_API_KEY );

        $args = [
            'body'        => json_encode( $body ),
            'headers'     => [
                'Authorization' => 'Bearer ' . NOVA_X_API_KEY,
                'Content-Type'  => 'application/json',
            ],
            'timeout'     => 60, // Give AI enough time to think
        ];

        $response = wp_remote_post( $this->api_url, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_body = wp_remote_retrieve_body( $response );
        $data = json_decode( $response_body, true );

        if ( isset( $data['error'] ) ) {
            return new WP_Error( 'openai_error', $data['error']['message'] );
        }

        return $data['choices'][0]['message']['content'] ?? '';
    }
}