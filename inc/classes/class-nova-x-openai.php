<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Nova_X_OpenAI {

    private $api_key;
    private $api_url = 'https://api.openai.com/v1/chat/completions';

    public function __construct() {
        // Read API key ONLY from WordPress options
        $this->api_key = trim( (string) get_option( 'nova_x_api_key', '' ) );
    }

    public function generate( $prompt ) {

        if ( empty( $this->api_key ) ) {
            return new WP_Error(
                'nova_x_missing_api_key',
                'OpenAI API key is missing. Please save it in Nova-X settings.'
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
                'nova_x_openai_error',
                'OpenAI request failed.',
                $data
            );
        }

        return trim( $data['choices'][0]['message']['content'] );
    }
}
