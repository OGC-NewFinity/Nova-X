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
            $providers = [ 'openai', 'anthropic', 'groq', 'mistral', 'gemini', 'claude', 'cohere' ];
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
            'openai'    => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            'groq'      => 'Groq',
            'mistral'   => 'Mistral AI',
            'gemini'    => 'Google Gemini',
            'claude'    => 'Anthropic Claude',
            'cohere'    => 'Cohere',
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
     * Attempts to get decrypted key from Token Manager first, then falls back to legacy storage.
     * 
     * @param string $provider Provider slug
     * @return string API key or empty string
     */
    public static function get_api_key( $provider ) {
        $provider = sanitize_key( $provider );
        
        if ( empty( $provider ) ) {
            return '';
        }

        // Try to get decrypted key from Token Manager (preferred method)
        $decrypted_key = Nova_X_Token_Manager::get_decrypted_key( $provider );
        
        if ( ! empty( $decrypted_key ) ) {
            return trim( (string) $decrypted_key );
        }

        // Attempt migration if no encrypted key exists
        Nova_X_Token_Manager::migrate_unencrypted_key( $provider );
        
        // Try again after migration
        $decrypted_key = Nova_X_Token_Manager::get_decrypted_key( $provider );
        if ( ! empty( $decrypted_key ) ) {
            return trim( (string) $decrypted_key );
        }

        // Fallback to legacy option (for backward compatibility)
        $option_name = self::get_api_key_option_name( $provider );
        $legacy_key = trim( (string) get_option( $option_name, '' ) );
        
        // Skip if it looks like a masked placeholder
        if ( strpos( $legacy_key, '*' ) !== false ) {
            return '';
        }

        return $legacy_key;
    }

    /**
     * Save API key for a provider with validation and encryption.
     * 
     * @param string $provider Provider slug
     * @param string $api_key API key
     * @return array Result with 'success' (bool) and 'message' (string)
     */
    public static function save_api_key( $provider, $api_key ) {
        $provider = sanitize_key( $provider );
        $api_key  = trim( (string) $api_key );
        
        if ( empty( $provider ) ) {
            return [
                'success' => false,
                'message' => 'Provider name is required.',
            ];
        }

        if ( empty( $api_key ) ) {
            return [
                'success' => false,
                'message' => 'API key cannot be empty.',
            ];
        }

        // Skip if it's a masked placeholder
        if ( strpos( $api_key, '*' ) !== false ) {
            return [
                'success' => false,
                'message' => 'Masked API key cannot be saved.',
            ];
        }

        // Validate API key format
        if ( ! class_exists( 'Nova_X_Provider_Rules' ) ) {
            $rules_path = defined( 'NOVA_X_PATH' ) 
                ? NOVA_X_PATH . 'inc/classes/class-nova-x-provider-rules.php'
                : plugin_dir_path( dirname( __FILE__ ) ) . 'inc/classes/class-nova-x-provider-rules.php';
            require_once $rules_path;
        }

        $validation = Nova_X_Provider_Rules::validate_key( $api_key, $provider );
        
        if ( ! $validation['valid'] ) {
            // Fire invalid key action hook
            do_action( 'nova_x_api_key_invalid', $provider, $validation['message'] );
            
            return [
                'success' => false,
                'message' => $validation['message'],
            ];
        }

        // Check if key has changed
        $existing_key = Nova_X_Token_Manager::get_decrypted_key( $provider );
        if ( $existing_key === $api_key ) {
            return [
                'success' => true,
                'message' => 'API key unchanged.',
            ];
        }

        // Encrypt and store the key using standardized format
        $encrypted = Nova_X_Token_Manager::store_encrypted_key( $provider, $api_key );
        
        if ( $encrypted ) {
            // Generate masked key for action hook
            $masked_key = self::mask_key( $api_key );
            
            // Fire saved key action hook
            do_action( 'nova_x_api_key_saved', $provider, $masked_key );
            
            return [
                'success' => true,
                'message' => 'API key saved successfully.',
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to encrypt and save API key.',
            ];
        }
    }

    /**
     * Check if provider has a valid API key.
     * 
     * @param string $provider Provider slug
     * @return bool True if valid key exists
     */
    public static function has_valid_key( $provider ) {
        $provider = sanitize_key( $provider );
        
        if ( empty( $provider ) ) {
            return false;
        }

        // Get the key
        $key = self::get_api_key( $provider );
        
        if ( empty( $key ) ) {
            return false;
        }

        // Validate the key format
        if ( ! class_exists( 'Nova_X_Provider_Rules' ) ) {
            $rules_path = defined( 'NOVA_X_PATH' ) 
                ? NOVA_X_PATH . 'inc/classes/class-nova-x-provider-rules.php'
                : plugin_dir_path( dirname( __FILE__ ) ) . 'inc/classes/class-nova-x-provider-rules.php';
            require_once $rules_path;
        }

        $validation = Nova_X_Provider_Rules::validate_key( $key, $provider );
        return $validation['valid'];
    }

    /**
     * Get all decrypted API keys (runtime use only - never expose to UI).
     * 
     * @return array Associative array of provider => decrypted_key
     */
    public static function get_all_keys() {
        $providers = self::get_supported_providers();
        $keys = [];
        
        foreach ( $providers as $provider ) {
            $key = self::get_api_key( $provider );
            if ( ! empty( $key ) ) {
                $keys[ $provider ] = $key;
            }
        }
        
        return $keys;
    }

    /**
     * Get encrypted key map for internal debug/admin use.
     * Returns encrypted values only - never decrypted keys.
     * 
     * @return array Associative array of provider => encrypted_key_data
     */
    public static function get_encrypted_map() {
        $providers = self::get_supported_providers();
        $encrypted_map = [];
        
        foreach ( $providers as $provider ) {
            $option_name = 'nova_x_key_' . sanitize_key( $provider );
            $encrypted_data = get_option( $option_name, '' );
            
            // Also check legacy format
            if ( empty( $encrypted_data ) ) {
                $legacy_option = 'nova_x_api_key_encrypted_' . sanitize_key( $provider );
                $encrypted_data = get_option( $legacy_option, '' );
            }
            
            if ( ! empty( $encrypted_data ) ) {
                $encrypted_map[ $provider ] = [
                    'exists' => true,
                    'length' => strlen( $encrypted_data ),
                    'preview' => substr( $encrypted_data, 0, 20 ) . '...',
                ];
            } else {
                $encrypted_map[ $provider ] = [
                    'exists' => false,
                ];
            }
        }
        
        return $encrypted_map;
    }

    /**
     * Get provider status with masked key for UI display.
     * 
     * @param string $provider Provider slug
     * @return array Status array with provider, status, and masked_key
     */
    public static function get_provider_status( $provider ) {
        $provider = sanitize_key( $provider );
        
        if ( empty( $provider ) ) {
            return [
                'provider'   => '',
                'status'     => 'invalid',
                'masked_key' => '',
            ];
        }

        $key = self::get_api_key( $provider );
        
        if ( empty( $key ) ) {
            return [
                'provider'   => $provider,
                'status'     => 'missing',
                'masked_key' => '',
            ];
        }

        // Validate the key
        if ( ! class_exists( 'Nova_X_Provider_Rules' ) ) {
            $rules_path = defined( 'NOVA_X_PATH' ) 
                ? NOVA_X_PATH . 'inc/classes/class-nova-x-provider-rules.php'
                : plugin_dir_path( dirname( __FILE__ ) ) . 'inc/classes/class-nova-x-provider-rules.php';
            require_once $rules_path;
        }

        $validation = Nova_X_Provider_Rules::validate_key( $key, $provider );
        
        $status = $validation['valid'] ? 'valid' : 'invalid';
        $masked_key = $validation['valid'] ? self::mask_key( $key ) : '';

        return [
            'provider'   => $provider,
            'status'     => $status,
            'masked_key' => $masked_key,
        ];
    }

    /**
     * Mask an API key for display purposes.
     * 
     * @param string $key API key to mask
     * @return string Masked key (e.g., 'sk-••••••abcd')
     */
    private static function mask_key( $key ) {
        $key = trim( (string) $key );
        
        if ( empty( $key ) || strlen( $key ) < 8 ) {
            return '••••••••';
        }

        // Show first 3 characters and last 4 characters
        $prefix = substr( $key, 0, 3 );
        $suffix = substr( $key, -4 );
        $mask_length = strlen( $key ) - 7;
        
        return $prefix . str_repeat( '•', max( 4, $mask_length ) ) . $suffix;
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
            // Check encrypted key first
            $has_encrypted_key = Nova_X_Token_Manager::key_exists( $provider );
            
            // Attempt migration if no encrypted key exists
            if ( ! $has_encrypted_key ) {
                Nova_X_Token_Manager::migrate_unencrypted_key( $provider );
                $has_encrypted_key = Nova_X_Token_Manager::key_exists( $provider );
            }
            
            // Fallback to legacy check
            $has_key = $has_encrypted_key || ! empty( self::get_api_key( $provider ) );
            
            $status[ $provider ] = [
                'name' => self::get_provider_name( $provider ),
                'has_key' => $has_key,
            ];
        }
        
        return $status;
    }
}

