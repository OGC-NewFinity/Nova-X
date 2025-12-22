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
        // We retrieve the key from WordPress Database options
        $this->api_key = get_option( 'nova_x_api_key', '' );
    }

    /**
     * Send a prompt to the AI and get a response.
     * * @param array $messages The chat history (Context).
     * @return array|WP_Error Response or Error.
     */
    public function chat( $messages ) {
        if ( empty( $this->api_key ) ) {
            return new WP_Error( 'no_api_key', 'API Key is missing. Please configure it in settings.' );
        }

        $body = [
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => 0.7, // Balance between creativity and precision
        ];

        $args = [
            'body'        => json_encode( $body ),
            'headers'     => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
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