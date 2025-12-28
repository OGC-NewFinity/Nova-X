<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_AI_Engine {

    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';
    private $provider = 'openai';

    public function __construct() {
        // Read API key ONLY from WordPress options
        $this->api_key = trim( (string) get_option( 'nova_x_api_key', '' ) );
    }

    /**
     * Set the AI provider to use.
     * 
     * @param string $provider Provider name (openai, gemini, claude, mistral, cohere, etc.)
     * @return void
     */
    public function set_provider( $provider ) {
        // Switch logic for OpenAI, Gemini, Claude, etc.
        $supported_providers = defined( 'NOVA_X_SUPPORTED_PROVIDERS' ) ? NOVA_X_SUPPORTED_PROVIDERS : [];
        
        if ( in_array( $provider, $supported_providers, true ) ) {
            $this->provider = sanitize_text_field( $provider );
            
            // TODO: Update API URL and endpoint based on provider
            // switch ( $this->provider ) {
            //     case 'openai':
            //         $this->api_url = 'https://api.openai.com/v1/chat/completions';
            //         break;
            //     case 'gemini':
            //         $this->api_url = 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent';
            //         break;
            //     case 'claude':
            //         $this->api_url = 'https://api.anthropic.com/v1/messages';
            //         break;
            //     // Add more providers as needed
            // }
        }
    }

    public function generate( $prompt ) {

        if ( empty( $this->api_key ) ) {
            return new WP_Error(
                'nova_x_missing_api_key',
                'AI API key is missing. Please save it in Nova-X settings.'
            );
        }

        $body = array(
            'model' => 'gpt-4o-mini',
            'messages' => array(
                array(
                    'role'    => 'user',
                    'content' => $prompt,
                ),
            ),
            'temperature' => 0.7,
        );

        $response = wp_remote_post(
            $this->api_url,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $body ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code !== 200 || empty( $data['choices'][0]['message']['content'] ) ) {
            return new WP_Error(
                'nova_x_ai_error',
                'AI request failed.',
                $data
            );
        }

        return trim( $data['choices'][0]['message']['content'] );
    }
}

