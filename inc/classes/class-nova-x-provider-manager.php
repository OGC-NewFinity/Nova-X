<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Nova_X_Provider_Manager
 * 
 * Load and manage available AI providers (OpenAI, Gemini, Claude, etc.)
 */
class Nova_X_Provider_Manager {

    /**
     * Get list of supported providers.
     * 
     * @return array Array of provider names
     */
    public static function get_supported_providers() {
        $providers = defined( 'NOVA_X_SUPPORTED_PROVIDERS' ) ? NOVA_X_SUPPORTED_PROVIDERS : [];
        
        if ( empty( $providers ) ) {
            // Fallback to default providers
            $providers = [ 'openai', 'gemini', 'claude', 'mistral', 'cohere' ];
        }
        
        return $providers;
    }

    /**
     * Get provider display name.
     * 
     * @param string $provider Provider slug
     * @return string Display name
     */
    public static function get_provider_name( $provider ) {
        $names = [
            'openai' => 'OpenAI',
            'gemini' => 'Google Gemini',
            'claude' => 'Anthropic Claude',
            'mistral' => 'Mistral AI',
            'cohere' => 'Cohere',
        ];
        
        return isset( $names[ $provider ] ) ? $names[ $provider ] : ucfirst( $provider );
    }

    /**
     * Get API key option name for a provider.
     * 
     * @param string $provider Provider slug
     * @return string Option name
     */
    public static function get_api_key_option_name( $provider ) {
        return 'nova_x_' . sanitize_key( $provider ) . '_api_key';
    }

    /**
     * Get stored API key for a provider.
     * 
     * @param string $provider Provider slug
     * @return string API key or empty string
     */
    public static function get_api_key( $provider ) {
        $option_name = self::get_api_key_option_name( $provider );
        return trim( (string) get_option( $option_name, '' ) );
    }

    /**
     * Save API key for a provider.
     * 
     * @param string $provider Provider slug
     * @param string $api_key API key
     * @return bool Success status
     */
    public static function save_api_key( $provider, $api_key ) {
        $option_name = self::get_api_key_option_name( $provider );
        $sanitized_key = sanitize_text_field( trim( $api_key ) );
        return update_option( $option_name, $sanitized_key, false );
    }

    /**
     * Validate provider slug.
     * 
     * @param string $provider Provider slug
     * @return bool True if valid
     */
    public static function is_valid_provider( $provider ) {
        $supported = self::get_supported_providers();
        return in_array( $provider, $supported, true );
    }

    /**
     * Get all providers with their status (has API key or not).
     * 
     * @return array Array of provider info
     */
    public static function get_providers_status() {
        $providers = self::get_supported_providers();
        $status = [];
        
        foreach ( $providers as $provider ) {
            $status[ $provider ] = [
                'name' => self::get_provider_name( $provider ),
                'has_key' => ! empty( self::get_api_key( $provider ) ),
            ];
        }
        
        return $status;
    }
}

